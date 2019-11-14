<?php

namespace Backend\Modules\Faq\Ajax;

use Backend\Core\Ajax\UpdateSequence;
use Backend\Modules\Faq\Domain\Category\Command\ReSequenceCategories;

final class CategoryReSequence extends UpdateSequence
{
    public function execute(): void
    {
        $this->setHandlerClass(ReSequenceCategories::class);

        parent::execute();
    }
}
