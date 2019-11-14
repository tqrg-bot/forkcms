<?php

namespace Backend\Modules\Faq\Domain\Category\Command;

use Backend\Modules\Faq\Domain\Category\Category;
use Backend\Modules\Faq\Domain\Category\CategoryRepository;

final class ReSequenceCategoriesHandler
{
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function handle(ReSequenceCategories $reSequenceCategories): void
    {
        /** @var Category[] $categories */
        $categories = $this->categoryRepository->createQueryBuilder('c', 'c.id')->getQuery()->getResult();
        foreach ($reSequenceCategories->getIds() as $sequence => $id) {
            $categories[$id]->changeSequence($sequence);
        }
    }
}
