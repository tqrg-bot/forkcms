<?php

namespace Backend\Modules\Faq\Installer;

use Backend\Core\Engine\Model;
use Backend\Core\Installer\ModuleInstaller;
use Backend\Modules\Faq\Domain\Category\Category;
use Backend\Modules\Faq\Domain\Category\CategoryTranslation;
use Backend\Modules\Faq\Domain\Question\Question;
use Backend\Modules\Faq\Domain\Question\QuestionTranslation;

final class Installer extends ModuleInstaller
{
    public function install(): void
    {
        $this->addModule('Faq');

        $this->importLocale(__DIR__ . '/Data/locale.xml');
        $this->configureEntities();
        $this->configureBackendNavigation();
        $this->configureBackendRights();
    }

    private function configureEntities(): void
    {
        Model::get('fork.entity.create_schema')->forEntityClasses(
            [
                Category::class,
                CategoryTranslation::class,
                Question::class,
                QuestionTranslation::class,
            ]
        );
    }

    private function configureBackendNavigation(): void
    {
        $navigationModulesId = $this->setNavigation(null, 'Modules');

        $navigationModuleId = $this->setNavigation($navigationModulesId, $this->getModule());
        $this->setNavigation(
            $navigationModuleId,
            'Question',
            'faq/question_index',
            [
                'faq/question_add',
                'faq/question_edit'
            ]
        );
        $this->setNavigation(
            $navigationModuleId,
            'Category',
            'faq/category_index',
            [
                'faq/category_add',
                'faq/category_edit'
            ]
        );
    }

    private function configureBackendRights(): void
    {
        $this->setModuleRights(1, $this->getModule());

        $this->setActionRights(1, $this->getModule(), 'CategoryAdd');
        $this->setActionRights(1, $this->getModule(), 'CategoryIndex');
        $this->setActionRights(1, $this->getModule(), 'CategoryDelete');
        $this->setActionRights(1, $this->getModule(), 'CategoryEdit');
        $this->setActionRights(1, $this->getModule(), 'QuestionAdd');
        $this->setActionRights(1, $this->getModule(), 'QuestionIndex');
        $this->setActionRights(1, $this->getModule(), 'QuestionDelete');
        $this->setActionRights(1, $this->getModule(), 'QuestionEdit');
    }
}
