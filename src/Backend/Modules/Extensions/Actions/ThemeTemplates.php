<?php

namespace Backend\Modules\Extensions\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionEdit as BackendBaseActionEdit;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Engine\DataGridDB as BackendDataGridDB;
use Backend\Core\Language\Language as BL;
use Backend\Modules\Extensions\Engine\Model as BackendExtensionsModel;

/**
 * This is the templates-action, it will display the templates-overview
 */
class ThemeTemplates extends BackendBaseActionEdit
{
    /**
     * All available themes
     *
     * @var array
     */
    private $availableThemes;

    /**
     * @var BackendDataGridDB
     */
    private $dataGrid;

    /**
     * The current selected theme
     *
     * @var string
     */
    private $selectedTheme;

    public function execute(): void
    {
        parent::execute();
        $this->loadData();
        $this->loadForm();
        $this->loadDataGrid();
        $this->parse();
        $this->display();
    }

    private function loadData(): void
    {
        // get data
        $this->selectedTheme = $this->getParameter('theme', 'string');

        // build available themes
        foreach (BackendExtensionsModel::getThemes() as $theme) {
            $this->availableThemes[$theme['value']] = $theme['label'];
        }

        // determine selected theme, based upon submitted form or default theme
        $this->selectedTheme = \SpoonFilter::getValue(
            $this->selectedTheme,
            array_keys($this->availableThemes),
            $this->get('fork.settings')->get('Core', 'theme', 'core')
        );
    }

    private function loadDataGrid(): void
    {
        // create datagrid
        $this->dataGrid = new BackendDataGridDB(
            BackendExtensionsModel::QRY_BROWSE_TEMPLATES,
            [$this->selectedTheme]
        );

        // check if this action is allowed
        if (BackendAuthentication::isAllowedAction('EditThemeTemplate')) {
            // set colum URLs
            $this->dataGrid->setColumnURL(
                'title',
                BackendModel::createURLForAction('EditThemeTemplate') . '&amp;id=[id]'
            );

            // add edit column
            $this->dataGrid->addColumn(
                'edit',
                null,
                BL::lbl('Edit'),
                BackendModel::createURLForAction('EditThemeTemplate') . '&amp;id=[id]',
                BL::lbl('Edit')
            );
        }
    }

    private function loadForm(): void
    {
        // create form
        $this->frm = new BackendForm('themes');

        // create elements
        $this->frm->addDropdown(
            'theme',
            $this->availableThemes,
            $this->selectedTheme,
            false,
            'form-control dontCheckBeforeUnload',
            'form-control dontCheckBeforeUnload'
        );
    }

    protected function parse(): void
    {
        parent::parse();

        // assign datagrid
        $this->tpl->assign('dataGrid', $this->dataGrid->getContent());

        // assign the selected theme, so we can propagate it to the add/edit actions.
        $this->tpl->assign('selectedTheme', rawurlencode($this->selectedTheme));
    }
}
