<?php

namespace Backend\Modules\MediaLibrary\Installer;

use Backend\Modules\MediaLibrary\Domain\MediaFolder\Command\CreateMediaFolder;
use Backend\Core\Engine\Model;
use Backend\Core\Installer\ModuleInstaller;
use Backend\Modules\MediaLibrary\Domain\MediaFolder\MediaFolder;
use Backend\Modules\MediaLibrary\Domain\MediaGroup\MediaGroup;
use Backend\Modules\MediaLibrary\Domain\MediaGroupMediaItem\MediaGroupMediaItem;
use Backend\Modules\MediaLibrary\Domain\MediaItem\MediaItem;

/**
 * Installer for the MediaLibrary module
 */
class Installer extends ModuleInstaller
{
    public function install(): void
    {
        $this->addModule('MediaLibrary');
        $this->importLocale(__DIR__ . '/Data/locale.xml');
        $this->createEntityTables();
        $this->configureModuleRights();
        $this->configureSettings();
        $this->configureBackendNavigation();
        $this->loadMediaFolders();
    }

    protected function configureBackendNavigation(): void
    {
        // Navigation for "modules"
        $this->setNavigation(
            null,
            $this->getModule(),
            'media_library/media_item_index',
            [
                'media_library/media_item_upload',
                'media_library/media_item_edit',
            ],
            3
        );
    }

    protected function configureModuleRights(): void
    {
        // Set module rights
        $this->setModuleRights(1, $this->getModule());
        $this->configureModuleRightsForMediaItem();
        $this->configureModuleRightsForMediaFolder();
    }

    protected function configureModuleRightsForMediaItem(): void
    {
        $this->setActionRights(1, $this->getModule(), 'MediaItemAddMovie'); // AJAX
        $this->setActionRights(1, $this->getModule(), 'MediaItemCleanup');
        $this->setActionRights(1, $this->getModule(), 'MediaItemDelete');
        $this->setActionRights(1, $this->getModule(), 'MediaItemEdit');
        $this->setActionRights(1, $this->getModule(), 'MediaItemFindAll'); // AJAX
        $this->setActionRights(1, $this->getModule(), 'MediaItemGetAllById'); // AJAX
        $this->setActionRights(1, $this->getModule(), 'MediaItemIndex');
        $this->setActionRights(1, $this->getModule(), 'MediaItemMassAction');
        $this->setActionRights(1, $this->getModule(), 'MediaItemUpload'); // Action and AJAX
    }

    protected function configureModuleRightsForMediaFolder(): void
    {
        $this->setActionRights(1, $this->getModule(), 'MediaFolderAdd'); // AJAX
        $this->setActionRights(1, $this->getModule(), 'MediaFolderDelete');
        $this->setActionRights(1, $this->getModule(), 'MediaFolderEdit'); // AJAX
        $this->setActionRights(1, $this->getModule(), 'MediaFolderFindAll'); // AJAX
        $this->setActionRights(1, $this->getModule(), 'MediaFolderGetCountsForGroup'); // AJAX
        $this->setActionRights(1, $this->getModule(), 'MediaFolderInfo'); // AJAX
        $this->setActionRights(1, $this->getModule(), 'MediaFolderMovie'); // AJAX
    }

    protected function configureSettings(): void
    {
        $this->setSetting($this->getModule(), 'upload_number_of_sharding_folders', 15);
    }

    private function createEntityTables(): void
    {
        Model::get('fork.entity.create_schema')->forEntityClasses(
            [
                MediaFolder::class,
                MediaGroup::class,
                MediaGroupMediaItem::class,
                MediaItem::class,
            ]
        );
    }

    protected function loadMediaFolders(): void
    {
        // Handle the create MediaFolder
        Model::get('command_bus')->handle(new CreateMediaFolder('default', 1));

        // Delete cache
        Model::get('media_library.cache.media_folder')->delete();
    }
}
