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
                'min' => '2',
                'hour' => '0',
                'command' => 'php /var/www/html/data/yii notification/transactions',
            ],
        ]);
        $cronTab->apply();
    }
}