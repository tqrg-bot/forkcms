<?php

namespace Backend\Modules\MediaLibrary\Actions;

use Backend\Core\Engine\Base\ActionEdit as BackendBaseActionEdit;
use Backend\Core\Engine\Model;
use Backend\Modules\MediaLibrary\Domain\MediaItem\Command\UpdateMediaItem;
use Backend\Modules\MediaLibrary\Domain\MediaItem\Exception\MediaItemNotFound;
use Backend\Modules\MediaLibrary\Domain\MediaItem\MediaItem;
use Backend\Modules\MediaLibrary\Domain\MediaItem\MediaItemType;

class MediaItemEdit extends BackendBaseActionEdit
{
    /** @var int */
    protected $folderId;

    /** @var string */
    protected $mediaItem;

    public function execute(): void
    {
        parent::execute();

        $this->parseJsFiles();

        /** @var MediaItem $mediaItem */
        $mediaItem = $this->getMediaItem();

        // Define folder id
        $this->folderId = $this->getParameter('folder', 'int', 0);

        $form = $this->createForm(
            MediaItemType::class,
            new UpdateMediaItem(
                $mediaItem
            )
        );

        $form->handleRequest($this->get('request'));

        if (!$form->isValid()) {
            $this->tpl->assign('folderId', $this->folderId);
            $this->tpl->assign('tree', $this->get('media_library.manager.tree')->getHTML());
            $this->header->addJsData(
                'MediaLibrary',
                'openedFolderId',
                $this->folderId ?? null
            );
            $this->tpl->assign('form', $form->createView());
            $this->tpl->assign('mediaItem', $mediaItem);
            $this->tpl->assign('backLink', $this->getBackLink());

            // Call parent
            $this->parse();
            $this->display();

            return;
        }

        /** @var UpdateMediaItem $updateMediaItem */
        $updateMediaItem = $form->getData();

        // Handle the MediaItem update
        $this->get('command_bus')->handle($updateMediaItem);

        $this->redirect(
            $this->getBackLink(
                [
                    'report' => 'media-item-edited',
                    'var' => $updateMediaItem->title,
                    'highlight' => 'row-' . $updateMediaItem->getMediaItemEntity()->getId(),
                    'id' => $updateMediaItem->getMediaItemEntity()->getId(),
                    'folder' => $this->folderId,
                ]
            )
        );
    }

    private function getMediaItem(): MediaItem
    {
        try {
            // Define MediaItem from repository
            return $this->get('media_library.repository.item')->findOneById(
                $this->getParameter('id', 'string')
            );
        } catch (MediaItemNotFound $mediaItemNotFound) {
            $this->redirect(
                $this->getBackLink(
                    [
                        'error' => 'media-item-not-existing',
                    ]
                )
            );
        }
    }

    private function getBackLink(array $parameters = []): string
    {
        return Model::createURLForAction(
            'MediaItemIndex',
            null,
            null,
            $parameters
        );
    }

    private function parseJsFiles(): void
    {
        $this->header->addJS('/src/Backend/Modules/Pages/Js/jstree/jquery.tree.js', null, false, true);
        $this->header->addJS('/src/Backend/Modules/Pages/Js/jstree/lib/jquery.cookie.js', null, false, true);
        $this->header->addJS('/src/Backend/Modules/Pages/Js/jstree/plugins/jquery.tree.cookie.js', null, false, true);
    }
}
