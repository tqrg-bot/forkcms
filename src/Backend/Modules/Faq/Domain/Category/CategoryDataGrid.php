<?php

namespace Backend\Modules\Faq\Domain\Category;

use Backend\Core\Engine\DataGridDatabase;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Model;
use Backend\Core\Language\Language;
use Backend\Core\Language\Locale;

class CategoryDataGrid extends DataGridDatabase
{
    public function __construct(Locale $locale)
    {
        parent::__construct(
            'SELECT c.id, ct.name
             FROM FaqCategory c
             INNER JOIN FaqCategoryTranslation ct ON c.id = ct.category_id AND ct.locale = :locale',
            ['locale' => $locale]
        );

        if (BackendAuthentication::isAllowedAction('CategoryEdit')) {
            $editUrl = Model::createUrlForAction('CategoryEdit', null, null, ['id' => '[id]'], false);
            $this->setColumnURL('name', $editUrl);
            $this->addColumn('edit', null, Language::lbl('Edit'), $editUrl, Language::lbl('Edit'));
        }
    }

    public static function getHtml(Locale $locale): string
    {
        return (new self($locale))->getContent();
    }
}
