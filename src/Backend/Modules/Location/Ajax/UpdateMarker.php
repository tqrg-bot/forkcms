<?php

namespace Backend\Modules\Location\Ajax;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\AjaxAction as BackendBaseAJAXAction;
use Backend\Core\Language\Language as BL;
use Backend\Modules\Location\Engine\Model as BackendLocationModel;

/**
 * This is an ajax handler
 */
class UpdateMarker extends BackendBaseAJAXAction
{
    public function execute(): void
    {
        parent::execute();

        // get parameters
        $itemId = trim(\SpoonFilter::getPostValue('id', null, '', 'int'));
        $lat = \SpoonFilter::getPostValue('lat', null, null, 'float');
        $lng = \SpoonFilter::getPostValue('lng', null, null, 'float');

        // validate id
        if ($itemId == 0) {
            $this->output(self::BAD_REQUEST, null, BL::err('NonExisting'));
        } else {
            //update
            $updateData = [
                'id' => $itemId,
                'lat' => $lat,
                'lng' => $lng,
                'language' => BL::getWorkingLanguage(),
            ];

            BackendLocationModel::update($updateData);

            // output
            $this->output(self::OK);
        }
    }
}
