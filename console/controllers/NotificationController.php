<?php

namespace console\controllers;

use backend\components\sender\MessageSender;
use common\models\Transaction;
use yii\console\Controller;

class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 1800;

    public function actionTransactions()
    {
        $messageSender = new MessageSender();

        $transactionsSample = Transaction::find()
            ->filterWhere(['not in', 'status', [1, 2]])
            ->andFilterWhere(['>', 'updated_at', time() - self::TRANSACTION_TRACKING_INTERVAL])
            ->select(['updated_at', 'id', 'merchant_transaction_id'])
            ->asArray()
            ->all();

        foreach ($transactionsSample as $item) {
            if ($item['merchant_transaction_id']) {
                $messageSender->send('Failed transaction id: ' . $item['id'] . '; Merchant transaction id: ' . $item['merchant_transaction_id']);
            }
        }
    }
}