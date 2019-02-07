<?php

namespace console\controllers;

use yii2tech\crontab\CronTab;
use yii\console\Controller;

class CronController extends Controller
{

    public function actionIndex()
    {
        $cronTab = new CronTab();
        $cronTab->setJobs([
            [
                'min' => '*/5',
                'command' => 'php /var/www/html/data/yii notification/transaction',
            ],
        ]);

        $cronTab->apply();
    }
}