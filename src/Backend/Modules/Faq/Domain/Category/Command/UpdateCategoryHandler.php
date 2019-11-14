<?php

namespace Backend\Modules\Faq\Domain\Category\Command;

use Backend\Modules\Faq\Domain\Category\Category;

final class UpdateCategoryHandler
{
    public function handle(UpdateCategory $updateCategory): void
    {
        Category::fromDataTransferObject($updateCategory);
    }
}
