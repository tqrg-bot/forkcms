<?php

namespace Backend\Modules\Faq\Actions;

use Backend\Core\Engine\Base\ActionIndex;
use Backend\Core\Language\Locale;
use Backend\Modules\Faq\Domain\Category\CategoryDataGrid;

final class CategoryIndex extends ActionIndex
{
    public function execute(): void
    {
        parent::execute();

        $this->template->assign('dataGrid', CategoryDataGrid::getHtml(Locale::workingLocale()));

        $this->parse();
        $this->display();
    }
}
