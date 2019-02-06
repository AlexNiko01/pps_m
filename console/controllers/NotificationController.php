<?php

namespace console\controllers;

use backend\components\sender\MessageSender;
use common\models\Transaction;
use yii\console\Controller;

//require '../../vendor/pps/pps-payment/Payment.php';

class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 1800;

    public function actionTransactions()
    {
        $messageSender = new MessageSender();

        $transactionsSample = Transaction::find()
            ->filterWhere(['not in', 'status', [1, 2]])
            ->andFilterWhere(['>', 'updated_at', time() - self::TRANSACTION_TRACKING_INTERVAL])
            ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id'])
            ->all();

        foreach ($transactionsSample as $item) {
            $messageSender->send('Failed transaction id: ' . $item->id
                . ' ; Merchant transaction id: ' . $item->merchant_transaction_id
                . ' ; time: ' . $item->updated_at
                . ' ; currency: ' . $item->currency
                . ' ; status: ' . Payment::getStatusDescription($item->status)
                . ' ; payment system: ' . $item->paymentSystem->name
                . '</br>');
        }
    }
}