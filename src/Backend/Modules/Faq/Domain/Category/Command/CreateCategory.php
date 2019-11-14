<?php

namespace Backend\Modules\Faq\Domain\Category\Command;

use Backend\Modules\Faq\Domain\Category\CategoryDataTransferObject;
use Common\Locale;

final class CreateCategory extends CategoryDataTransferObject
{
    public function __construct()
    {
        parent::__construct();
    }
}
