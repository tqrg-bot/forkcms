<?php

namespace Backend\Modules\Faq\Actions;

use Backend\Core\Engine\Base\ActionDelete;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Form\Type\DeleteType;
use Backend\Modules\Faq\Domain\Category\Command\DeleteCategory;
use Backend\Modules\Faq\Domain\Category\Category;
use Backend\Modules\Faq\Domain\Category\CategoryRepository;

final class CategoryDelete extends ActionDelete
{
    public function execute(): void
    {
        $category = $this->getCategory();
        if (!$category instanceof Category) {
            $this->redirect($this->getBackLink(['error' => 'non-existing']));

            return;
        }

        $this->get('command_bus')->handle(new DeleteCategory($category));

        $this->redirect($this->getBackLink(['report' => 'deleted', 'var' => $category]));
    }

    private function getBackLink(array $parameters = []): string
    {
        return BackendModel::createUrlForAction(
            'CategoryIndex',
            null,
            null,
            $parameters
        );
    }

    private function getCategory(): ?Category
    {
        $deleteForm = $this->createForm(DeleteType::class, null, ['module' => $this->getModule()]);
        $deleteForm->handleRequest($this->getRequest());

        if (!$deleteForm->isSubmitted() || !$deleteForm->isValid()) {
            return null;
        }

        return $this->get(CategoryRepository::class)->find($deleteForm->getData()['id']);
    }
}
