<?php

namespace Backend\Modules\Analytics\Widgets;

use Backend\Core\Engine\Base\Widget;
use Google_Auth_Exception;

/**
 * This widget will show recent visits
 */
class RecentVisits extends Widget
{
    public function execute(): void
    {
        $startDate = strtotime('-1 week', mktime(0, 0, 0));
        $endDate = mktime(0, 0, 0);

        try {
            $analytics = $this->get('analytics.connector');

            $this->tpl->assign(
                'visitors_graph_data',
                $analytics->getVisitorsGraphData($startDate, $endDate)
            );

            $this->header->addJS('highcharts.js', 'Core', false);
            $this->header->addJS('Analytics.js', 'Analytics', false);
            $this->display();
        } catch (Google_Auth_Exception $e) {
            // do nothing, analyticis is probably not set up yet.
        }
    }
}
