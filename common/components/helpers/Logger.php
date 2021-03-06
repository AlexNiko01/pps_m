<?php

namespace common\components\helpers;

use yii\base\InvalidConfigException;
use yii\log\FileTarget;
use yii\log\LogRuntimeException;

class Logger
{
    /**
     * @param string $msg
     */
    public static function recodeLog(string $msg): void
    {
        try {
            $log = new FileTarget();
            $log->logFile = \Yii::$app->getRuntimePath() . '/logs/wx_debug_' . date("Y-m-d") . '.log';
            $log->messages[] = [$msg, 1, 'application', microtime(true)];
            $log->export();
        } catch (InvalidConfigException $e) {

        } catch (LogRuntimeException $e) {

        }
    }
}