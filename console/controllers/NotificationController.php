<?php

namespace console\controllers;

use common\models\Transaction;
use yii\console\Controller;
use yii\log\FileTarget;


class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 60000;


    public function actionTransaction(): void
    {
        $transactionsSample = Transaction::find()
            ->filterWhere(['not in', 'status', [0, 1, 2]])
            ->andFilterWhere(['>', 'updated_at', time() - self::TRANSACTION_TRACKING_INTERVAL])
            ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id'])
            ->limit(1)
            ->all();

        foreach ($transactionsSample as $item) {
            \Yii::$app->sender->send([
                'Failed transaction id: ' . $item->id . ';',
                'Merchant transaction id: ' . $item->merchant_transaction_id . ';',
                'Time: ' . date('m-d-Y h:i:s', $item->updated_at) . ';',
                'Currency: ' . $item->currency . ';',
                'Status: ' . \pps\payment\Payment::getStatusDescription($item->status) . ';',
                'Payment system: ' . $item->paymentSystem->name . '.'
            ]);
        };
    }

    /**
     * @param $msg
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\log\LogRuntimeException
     */
    public function recodeLog($msg)
    {
        $log = new FileTarget();
        $log->logFile = \Yii::$app->getRuntimePath() . '/logs/wx_debug_' . date("Y-m-d") . '.log';
        $log->messages[] = [$msg, 1, 'application', microtime(true)];
        $log->export();
    }
}