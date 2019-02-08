<?php

namespace console\controllers;

use common\models\Transaction;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\log\FileTarget;
use yii\log\LogRuntimeException;


class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 46000;


    public function actionTransaction(): void
    {
        $this->recodeLog('1');
        $transactionsSample = Transaction::find()
            ->filterWhere(['not in', 'status', [0, 1, 2]])
            ->andFilterWhere(['>', 'updated_at', time() - self::TRANSACTION_TRACKING_INTERVAL])
            ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id'])
            ->all();

        foreach ($transactionsSample as $item) {
            \Yii::$app->sender->send('<b>Failed transaction id:</b> ' . $item->id
                . ' ;</br><b>Merchant transaction id:</b> ' . $item->merchant_transaction_id
                . ' ;</br><b>time:</b> ' . date('m-d-Y h:i:s', $item->updated_at)
                . ' ;</br><b>currency:</b> ' . $item->currency
                . ' ;</br><b>status:</b> ' . \pps\payment\Payment::getStatusDescription($item->status)
                . ' ;</br><b>payment system:</b> ' . $item->paymentSystem->name
                . '</br></br>'
            );
        };
        $this->recodeLog('2');
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