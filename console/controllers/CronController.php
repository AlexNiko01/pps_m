<?php

namespace console\controllers;

use yii2tech\crontab\CronTab;
use yii\console\Controller;

class CronController extends Controller
{

    /**
     * Activates all cron tasks.
     * Run this action when installing the project on the server
     */
    public function actionIndex()
    {
        $cronTab = new CronTab();
        $cronTab->setJobs([
            [
                'command' => '/usr/local/bin/php /var/www/html/data/yii notification/transaction',
            ],
            [
                'min' => '00',
                'hour' => '12',
                'command' => '/usr/local/bin/php /var/www/html/data/yii notification/payment-system',
            ],
            [
                'min' => '00',
                'hour' => '12',
                'command' => '/usr/local/bin/php /var/www/html/data/yii notification/pps-check',
            ],
            [
                'min' => '05',
                'hour' => '12',
                'command' => '/usr/local/bin/php /var/www/html/data/yii notification/check-projects',
            ]
        ]);
        $cronTab->apply();
    }
}