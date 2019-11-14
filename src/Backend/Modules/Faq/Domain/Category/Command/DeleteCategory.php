<?php

namespace Backend\Modules\Faq\Domain\Category\Command;

use Backend\Modules\Faq\Domain\Category\Category;

final class DeleteCategory
{
    /** @var Category */
    private $category;

    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
