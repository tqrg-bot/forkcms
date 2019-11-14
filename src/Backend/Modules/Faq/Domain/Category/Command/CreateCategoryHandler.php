<?php

namespace Backend\Modules\Faq\Domain\Category\Command;

use Backend\Modules\Faq\Domain\Category\Category;
use Backend\Modules\Faq\Domain\Category\CategoryRepository;

final class CreateCategoryHandler
{
    /** @var CategoryRepository */
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function handle(CreateCategory $createCategory): void
    {
        $createCategory->sequence = $this->categoryRepository->getNextSequence();

        $this->categoryRepository->add(
            Category::fromDataTransferObject($createCategory)
        );
    }
}
