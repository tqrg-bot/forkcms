<?php

namespace Frontend\Modules\Blog\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Common\Cookie as CommonCookie;
use Frontend\Core\Engine\Base\Block as FrontendBaseBlock;
use Frontend\Core\Engine\Form as FrontendForm;
use Frontend\Core\Language\Language as FL;
use Frontend\Core\Engine\Model as FrontendModel;
use Frontend\Core\Engine\Navigation as FrontendNavigation;
use Frontend\Modules\Blog\Engine\Model as FrontendBlogModel;
use Frontend\Modules\Tags\Engine\Model as FrontendTagsModel;

/**
 * This is the detail-action
 */
class Detail extends FrontendBaseBlock
{
    /**
     * The comments
     *
     * @var array
     */
    private $comments;

    /**
     * Form instance
     *
     * @var FrontendForm
     */
    private $frm;

    /**
     * The blogpost
     *
     * @var array
     */
    private $record;

    /**
     * The settings
     *
     * @var array
     */
    private $settings;

    public function execute(): void
    {
        parent::execute();
        $this->loadTemplate();
        $this->getData();
        $this->buildForm();
        $this->validateForm();
        $this->parse();
    }

    private function getData(): void
    {
        // validate incoming parameters
        if ($this->URL->getParameter(1) === null) {
            $this->redirect(FrontendNavigation::getURL(404));
        }

        // load revision
        if ($this->URL->getParameter('revision', 'int') != 0) {
            // get data
            $this->record = FrontendBlogModel::getRevision(
                $this->URL->getParameter(1),
                $this->URL->getParameter('revision', 'int')
            );

            // add no-index, so the draft won't get accidentally indexed
            $this->header->addMetaData(['name' => 'robots', 'content' => 'noindex, nofollow'], true);
        } else {
            // get by URL
            $this->record = FrontendBlogModel::get($this->URL->getParameter(1));
        }

        // anything found?
        if (empty($this->record)) {
            $this->redirect(FrontendNavigation::getURL(404));
        }

        // get comments
        $this->comments = FrontendBlogModel::getComments($this->record['id']);

        // get tags
        $this->record['tags'] = FrontendTagsModel::getForItem('Blog', $this->record['id']);

        // get settings
        $this->settings = $this->get('fork.settings')->getForModule('Blog');

        // overwrite URLs
        $this->record['category_full_url'] = FrontendNavigation::getURLForBlock('Blog', 'Category') .
                                             '/' . $this->record['category_url'];
        $this->record['full_url'] = FrontendNavigation::getURLForBlock('Blog', 'Detail') . '/' . $this->record['url'];
        $this->record['allow_comments'] = ($this->record['allow_comments'] == 'Y');
        $this->record['comments_count'] = count($this->comments);

        // reset allow comments
        if (!$this->settings['allow_comments']) {
            $this->record['allow_comments'] = false;
        }
    }

    private function buildForm(): void
    {
        // create form
        $this->frm = new FrontendForm('commentsForm');
        $this->frm->setAction($this->frm->getAction() . '#' . FL::act('Comment'));

        // init vars
        $author = (CommonCookie::exists('comment_author')) ? CommonCookie::get('comment_author') : null;
        $email = (CommonCookie::exists('comment_email') && \SpoonFilter::isEmail(CommonCookie::get('comment_email'))) ? CommonCookie::get('comment_email') : null;
        $website = (CommonCookie::exists('comment_website') && \SpoonFilter::isURL(CommonCookie::get('comment_website'))) ? CommonCookie::get('comment_website') : 'http://';

        // create elements
        $this->frm->addText('author', $author)->setAttributes(['required' => null]);
        $this->frm->addText('email', $email)->setAttributes(['required' => null, 'type' => 'email']);
        $this->frm->addText('website', $website, null);
        $this->frm->addTextarea('message')->setAttributes(['required' => null]);
    }

    private function parse(): void
    {
        // get RSS-link
        $rssTitle = $this->get('fork.settings')->get('Blog', 'rss_title_' . LANGUAGE);
        $rssLink = FrontendNavigation::getURLForBlock('Blog', 'Rss');

        // add RSS-feed
        $this->header->addRssLink($rssTitle, $rssLink);

        // get RSS-link for the comments
        $rssCommentTitle = vsprintf(FL::msg('CommentsOn'), [$this->record['title']]);
        $rssCommentsLink = FrontendNavigation::getURLForBlock('Blog', 'ArticleCommentsRss') .
                           '/' . $this->record['url'];

        // add RSS-feed into the metaCustom
        $this->header->addRssLink($rssCommentTitle, $rssCommentsLink);

        // add specified image
        if (isset($this->record['image']) && $this->record['image'] != '') {
            $this->header->addOpenGraphImage(
                FRONTEND_FILES_URL . '/blog/images/source/' . $this->record['image']
            );
        }

        // Open Graph-data: add images from content
        $this->header->extractOpenGraphImages($this->record['text']);

        // Open Graph-data: add additional OpenGraph data
        $this->header->addOpenGraphData('title', $this->record['title'], true);
        $this->header->addOpenGraphData('type', 'article', true);
        $this->header->addOpenGraphData('url', SITE_URL . $this->record['full_url'], true);
        $this->header->addOpenGraphData(
            'site_name',
            $this->get('fork.settings')->get('Core', 'site_title_' . LANGUAGE, SITE_DEFAULT_TITLE),
            true
        );
        $this->header->addOpenGraphData(
            'description',
            ($this->record['meta_description_overwrite'] == 'Y') ? $this->record['meta_description'] : $this->record['title'],
            true
        );

        // Twitter Card
        $imgURL = FRONTEND_FILES_URL . '/blog/images/source/' . $this->record['image'];
        $this->header->setTwitterCard($this->record['title'], $this->record['meta_description'], $imgURL);

        // when there are 2 or more categories with at least one item in it,
        // the category will be added in the breadcrumb
        if (count(FrontendBlogModel::getAllCategories()) > 1) {
            $this->breadcrumb->addElement(
                $this->record['category_title'],
                FrontendNavigation::getURLForBlock('Blog', 'Category') . '/' . $this->record['category_url']
            );
        }

        // add into breadcrumb
        $this->breadcrumb->addElement($this->record['title']);

        // set meta
        $this->header->setPageTitle($this->record['meta_title'], ($this->record['meta_title_overwrite'] == 'Y'));
        $this->header->addMetaDescription(
            $this->record['meta_description'],
            ($this->record['meta_description_overwrite'] == 'Y')
        );
        $this->header->addMetaKeywords(
            $this->record['meta_keywords'],
            ($this->record['meta_keywords_overwrite'] == 'Y')
        );
        $this->header->setMetaCustom($this->record['meta_custom']);

        // advanced SEO-attributes
        if (isset($this->record['meta_data']['seo_index'])) {
            $this->header->addMetaData(
                ['name' => 'robots', 'content' => $this->record['meta_data']['seo_index']]
            );
        }
        if (isset($this->record['meta_data']['seo_follow'])) {
            $this->header->addMetaData(
                ['name' => 'robots', 'content' => $this->record['meta_data']['seo_follow']]
            );
        }

        $this->header->setCanonicalUrl($this->record['full_url']);

        // assign article
        $this->tpl->assign('item', $this->record);

        // count comments
        $commentCount = count($this->comments);

        // assign the comments
        $this->tpl->assign('commentsCount', $commentCount);
        $this->tpl->assign('comments', $this->comments);

        // options
        if ($commentCount > 1) {
            $this->tpl->assign('blogCommentsMultiple', true);
        }

        // parse the form
        $this->frm->parse($this->tpl);

        // some options
        if ($this->URL->getParameter('comment', 'string') == 'moderation') {
            $this->tpl->assign(
                'commentIsInModeration',
                true
            );
        }
        if ($this->URL->getParameter('comment', 'string') == 'spam') {
            $this->tpl->assign('commentIsSpam', true);
        }
        if ($this->URL->getParameter('comment', 'string') == 'true') {
            $this->tpl->assign('commentIsAdded', true);
        }

        // assign settings
        $this->tpl->assign('settings', $this->settings);

        $navigation = FrontendBlogModel::getNavigation($this->record['id']);

        // set previous and next link for usage with Flip ahead
        if (!empty($navigation['previous'])) {
            $this->header->addLink(
                [
                     'rel' => 'prev',
                     'href' => SITE_URL . $navigation['previous']['url'],
                ]
            );
        }
        if (!empty($navigation['next'])) {
            $this->header->addLink(
                [
                     'rel' => 'next',
                     'href' => SITE_URL . $navigation['next']['url'],
                ]
            );
        }

        // assign navigation
        $this->tpl->assign('navigation', $navigation);
    }

    private function validateForm(): void
    {
        // get settings
        $commentsAllowed = (isset($this->settings['allow_comments']) && $this->settings['allow_comments']);

        // comments aren't allowed so we don't have to validate
        if (!$commentsAllowed) {
            return;
        }

        // is the form submitted
        if ($this->frm->isSubmitted()) {
            // cleanup the submitted fields, ignore fields that were added by hackers
            $this->frm->cleanupFields();

            // does the key exists?
            if (\SpoonSession::exists('blog_comment_' . $this->record['id'])) {
                // calculate difference
                $diff = time() - (int) \SpoonSession::get('blog_comment_' . $this->record['id']);

                // calculate difference, it it isn't 10 seconds the we tell the user to slow down
                if ($diff < 10 && $diff != 0) {
                    $this->frm->getField('message')->addError(FL::err('CommentTimeout'));
                }
            }

            // validate required fields
            $this->frm->getField('author')->isFilled(FL::err('AuthorIsRequired'));
            $this->frm->getField('email')->isEmail(FL::err('EmailIsRequired'));
            $this->frm->getField('message')->isFilled(FL::err('MessageIsRequired'));

            // validate optional fields
            if ($this->frm->getField('website')->isFilled() && $this->frm->getField('website')->getValue() != 'http://'
            ) {
                $this->frm->getField('website')->isURL(FL::err('InvalidURL'));
            }

            // no errors?
            if ($this->frm->isCorrect()) {
                // get module setting
                $spamFilterEnabled = (isset($this->settings['spamfilter']) && $this->settings['spamfilter']);
                $moderationEnabled = (isset($this->settings['moderation']) && $this->settings['moderation']);

                // reformat data
                $author = $this->frm->getField('author')->getValue();
                $email = $this->frm->getField('email')->getValue();
                $website = $this->frm->getField('website')->getValue();
                if (trim($website) == '' || $website == 'http://') {
                    $website = null;
                }
                $text = $this->frm->getField('message')->getValue();

                // build array
                $comment = [];
                $comment['post_id'] = $this->record['id'];
                $comment['language'] = LANGUAGE;
                $comment['created_on'] = FrontendModel::getUTCDate();
                $comment['author'] = $author;
                $comment['email'] = $email;
                $comment['website'] = $website;
                $comment['text'] = $text;
                $comment['status'] = 'published';
                $comment['data'] = serialize(['server' => $_SERVER]);

                // get URL for article
                $permaLink = $this->record['full_url'];
                $redirectLink = $permaLink;

                // is moderation enabled
                if ($moderationEnabled) {
                    // if the commenter isn't moderated before alter the
                    // comment status so it will appear in the moderation queue
                    if (!FrontendBlogModel::isModerated($author, $email)) {
                        $comment['status'] = 'moderation';
                    }
                }

                // should we check if the item is spam
                if ($spamFilterEnabled) {
                    // check for spam
                    $result = FrontendModel::isSpam($text, SITE_URL . $permaLink, $author, $email, $website);

                    // if the comment is spam alter the comment status so it will appear in the spam queue
                    if ($result) {
                        $comment['status'] = 'spam';
                    } elseif ($result == 'unknown') {
                        // if the status is unknown then we should moderate it manually
                        $comment['status'] = 'moderation';
                    }
                }

                // insert comment
                $comment['id'] = FrontendBlogModel::insertComment($comment);

                // append a parameter to the URL so we can show moderation
                if (mb_strpos($redirectLink, '?') === false) {
                    if ($comment['status'] == 'moderation') {
                        $redirectLink .= '?comment=moderation#' . FL::act('Comment');
                    }
                    if ($comment['status'] == 'spam') {
                        $redirectLink .= '?comment=spam#' . FL::act('Comment');
                    }
                    if ($comment['status'] == 'published') {
                        $redirectLink .= '?comment=true#comment-' . $comment['id'];
                    }
                } else {
                    if ($comment['status'] == 'moderation') {
                        $redirectLink .= '&comment=moderation#' . FL::act('Comment');
                    }
                    if ($comment['status'] == 'spam') {
                        $redirectLink .= '&comment=spam#' . FL::act('Comment');
                    }
                    if ($comment['status'] == 'published') {
                        $redirectLink .= '&comment=true#comment-' . $comment['id'];
                    }
                }

                // set title
                $comment['post_title'] = $this->record['title'];
                $comment['post_url'] = $this->record['url'];

                // notify the admin
                FrontendBlogModel::notifyAdmin($comment);

                // store timestamp in session so we can block excessive usage
                \SpoonSession::set('blog_comment_' . $this->record['id'], time());

                // store author-data in cookies
                try {
                    CommonCookie::set('comment_author', $author);
                    CommonCookie::set('comment_email', $email);
                    CommonCookie::set('comment_website', $website);
                } catch (\Exception $e) {
                    // settings cookies isn't allowed, but because this isn't a real problem we ignore the exception
                }

                // redirect
                $this->redirect($redirectLink);
            }
        }
    }
}
