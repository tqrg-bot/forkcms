<?php

namespace Backend\Modules\Faq\Domain\Category\Command;

use Backend\Modules\Faq\Domain\Category\CategoryDataTransferObject;
use Backend\Modules\Faq\Domain\Category\Category;

final class UpdateCategory extends CategoryDataTransferObject
{
    public function __construct(Category $category)
    {
        // make sure we have an existing category
        parent::__construct($category);
    }
}
