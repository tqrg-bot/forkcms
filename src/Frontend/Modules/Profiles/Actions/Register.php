<?php

namespace Frontend\Modules\Profiles\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Common\Mailer\Message;
use Frontend\Core\Engine\Base\Block as FrontendBaseBlock;
use Frontend\Core\Engine\Form as FrontendForm;
use Frontend\Core\Language\Language as FL;
use Frontend\Core\Engine\Model as FrontendModel;
use Frontend\Core\Engine\Navigation as FrontendNavigation;
use Frontend\Modules\Profiles\Engine\Authentication as FrontendProfilesAuthentication;
use Frontend\Modules\Profiles\Engine\Model as FrontendProfilesModel;
use Common\Exception\RedirectException as RedirectException;

/**
 * Register a profile.
 */
class Register extends FrontendBaseBlock
{
    /**
     * FrontendForm instance.
     *
     * @var FrontendForm
     */
    private $frm;

    public function execute(): void
    {
        parent::execute();

        $this->loadTemplate();

        // profile not logged in
        if (!FrontendProfilesAuthentication::isLoggedIn()) {
            $this->buildForm();
            $this->validateForm();
            $this->parse();
        } elseif ($this->URL->getParameter('sent') == true) {
            // just registered so show success message
            $this->parse();
        } else {
            // already logged in, so you can not register
            $this->redirect(SITE_URL);
        }
    }

    private function buildForm(): void
    {
        $this->frm = new FrontendForm('register', null, null, 'registerForm');
        $this->frm->addText('display_name');
        $this->frm->addText('email')->setAttributes(['required' => null, 'type' => 'email']);
        $this->frm->addPassword('password')->setAttributes(
            [
                'required' => null,
                'data-role' => 'fork-new-password',
            ]
        );
        $this->frm->addCheckbox('show_password')->setAttributes(
            ['data-role' => 'fork-toggle-visible-password']
        );
    }

    private function parse(): void
    {
        // e-mail was sent?
        if ($this->URL->getParameter('sent') == 'true') {
            // show message
            $this->tpl->assign('registerIsSuccess', true);

            // hide form
            $this->tpl->assign('registerHideForm', true);
        } else {
            $this->frm->parse($this->tpl);
        }
    }

    private function validateForm(): void
    {
        // is the form submitted
        if ($this->frm->isSubmitted()) {
            // get fields
            $txtDisplayName = $this->frm->getField('display_name');
            $txtEmail = $this->frm->getField('email');
            $txtPassword = $this->frm->getField('password');

            // check email
            if ($txtEmail->isFilled(FL::getError('EmailIsRequired'))) {
                // valid email?
                if ($txtEmail->isEmail(FL::getError('EmailIsInvalid'))) {
                    // email already exists?
                    if (FrontendProfilesModel::existsByEmail($txtEmail->getValue())) {
                        // set error
                        $txtEmail->setError(FL::getError('EmailExists'));
                    }
                }
            }

            // check password
            $txtPassword->isFilled(FL::getError('PasswordIsRequired'));
            $txtDisplayName->isFilled(FL::getError('FieldIsRequired'));

            // no errors
            if ($this->frm->isCorrect()) {
                // init values
                $settings = [];
                $values = [];

                // generate salt
                $settings['salt'] = FrontendProfilesModel::getRandomString();
                $settings['language'] = LANGUAGE;

                // values
                $values['email'] = $txtEmail->getValue();
                $values['password'] = FrontendProfilesModel::getEncryptedString(
                    $txtPassword->getValue(),
                    $settings['salt']
                );
                $values['status'] = 'inactive';
                $values['display_name'] = $txtDisplayName->getValue();
                $values['registered_on'] = FrontendModel::getUTCDate();
                $values['last_login'] = FrontendModel::getUTCDate(null, 0);

                /*
                 * Add a profile.
                 * We use a try-catch statement to catch errors when more users sign up simultaneously.
                 */
                try {
                    // insert profile
                    $profileId = FrontendProfilesModel::insert($values);

                    // use the profile id as url until we have an actual url
                    FrontendProfilesModel::update(
                        $profileId,
                        ['url' => FrontendProfilesModel::getUrl($values['display_name'])]
                    );

                    // generate activation key
                    $settings['activation_key'] = FrontendProfilesModel::getEncryptedString(
                        $profileId . microtime(),
                        $settings['salt']
                    );

                    // set settings
                    FrontendProfilesModel::setSettings($profileId, $settings);

                    // login
                    FrontendProfilesAuthentication::login($profileId);

                    // send email
                    $from = $this->get('fork.settings')->get('Core', 'mailer_from');
                    $replyTo = $this->get('fork.settings')->get('Core', 'mailer_reply_to');
                    $message = Message::newInstance(FL::getMessage('RegisterSubject'))
                        ->setFrom([$from['email'] => $from['name']])
                        ->setTo([$txtEmail->getValue() => ''])
                        ->setReplyTo([$replyTo['email'] => $replyTo['name']])
                        ->parseHtml(
                            '/Profiles/Layout/Templates/Mails/Register.html.twig',
                            [
                                'activationUrl' => SITE_URL . FrontendNavigation::getURLForBlock('Profiles', 'Activate')
                                                   . '/' . $settings['activation_key'],
                            ],
                            true
                        )
                    ;
                    $this->get('mailer')->send($message);

                    // redirect
                    $this->redirect(SITE_URL . $this->URL->getQueryString() . '?sent=true');
                } catch (\Exception $e) {
                    // make sure RedirectExceptions get thrown
                    if ($e instanceof RedirectException) {
                        throw $e;
                    }

                    // when debugging we need to see the exceptions
                    if ($this->getContainer()->getParameter('kernel.debug')) {
                        throw $e;
                    }

                    // show error
                    $this->tpl->assign('registerHasFormError', true);
                }
            } else {
                $this->tpl->assign('registerHasFormError', true);
            }
        }
    }
}
