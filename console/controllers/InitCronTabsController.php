<?php

namespace console\controllers;

use yii2tech\crontab\CronTab;
use yii\console\Controller;

class InitCronTabsController extends Controller
{

    public function actionIndex()
    {
        $cronTab = new CronTab();
        $cronTab->setJobs([
            [
                'min' => '0',
                'hour' => '0',
                'command' => 'php /path/to/project/yii some-cron',
            ],
            [
                'line' => '0 0 * * * php /path/to/project/yii another-cron'
            ]
        ]);
        $cronTab->apply();
    }
}