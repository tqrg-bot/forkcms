<?php

namespace Backend\Modules\Faq\Domain\Category\Command;

use Backend\Modules\Faq\Domain\Category\CategoryRepository;

final class DeleteCategoryHandler
{
    /** @var CategoryRepository */
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function handle(DeleteCategory $deleteCategory): void
    {
        $this->categoryRepository->remove($deleteCategory->getCategory());
    }
}
