<?php

namespace console\controllers;

use common\models\Transaction;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\log\FileTarget;
use yii\log\LogRuntimeException;


class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 900;


    public function actionTransaction(): void
    {
        try {
            $this->recodeLog('log1');
        } catch (InvalidConfigException $e) {
        } catch (LogRuntimeException $e) {
        }
        $transactionsSample = Transaction::find()
            ->filterWhere(['not in', 'status', [1, 2]])
            ->andFilterWhere(['>', 'updated_at', time() - self::TRANSACTION_TRACKING_INTERVAL])
            ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id'])
            ->all();

        foreach ($transactionsSample as $item) {
            try {
                $this->recodeLog('log2');
            } catch (InvalidConfigException $e) {
            } catch (LogRuntimeException $e) {
            }
            \Yii::$app->sender->send('Failed transaction id: ' . $item->id
                . ' ; Merchant transaction id: ' . $item->merchant_transaction_id
                . ' ; time: ' . $item->updated_at
                . ' ; currency: ' . $item->currency
                . ' ; status: ' . \pps\payment\Payment::getStatusDescription($item->status)
                . ' ; payment system: ' . $item->paymentSystem->name
            );
        };
        try {
            $this->recodeLog('log3');
        } catch (InvalidConfigException $e) {
        } catch (LogRuntimeException $e) {
        }
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