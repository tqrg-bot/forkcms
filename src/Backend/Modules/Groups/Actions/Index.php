<?php

namespace Backend\Modules\Groups\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionIndex as BackendBaseActionIndex;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\DataGridDB as BackendDataGridDB;
use Backend\Core\Language\Language as BL;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Modules\Groups\Engine\Model as BackendGroupsModel;

/**
 * This is the index-action (default), it will display the groups-overview
 */
class Index extends BackendBaseActionIndex
{
    public function execute(): void
    {
        parent::execute();
        $this->loadDataGrid();
        $this->parse();
        $this->display();
    }

    public function loadDataGrid(): void
    {
        $this->dataGrid = new BackendDataGridDB(BackendGroupsModel::QRY_BROWSE);

        // check if this action is allowed
        if (BackendAuthentication::isAllowedAction('Edit')) {
            $this->dataGrid->setColumnURL('name', BackendModel::createURLForAction('Edit') . '&amp;id=[id]');
            $this->dataGrid->setColumnURL('num_users', BackendModel::createURLForAction('Edit') . '&amp;id=[id]#tabUsers');
            $this->dataGrid->addColumn('edit', null, BL::lbl('Edit'), BackendModel::createURLForAction('Edit') . '&amp;id=[id]');
        }
    }

    protected function parse(): void
    {
        parent::parse();

        $this->tpl->assign('dataGrid', $this->dataGrid->getContent());
    }
}
