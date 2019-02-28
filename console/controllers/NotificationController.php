<?php

namespace console\controllers;

use common\models\Transaction;
use yii\console\Controller;


class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 60;
    const TESTING_MERCHANT_ID = 5;

    /**
     * Action for checking failed transaction and sending notification
     */
    public function actionTransaction(): void
    {
        $transactionsSample = Transaction::find()
            ->filterWhere(['not in', 'status', [0, 1, 2]])
            ->andFilterWhere(['>', 'updated_at', time() - self::TRANSACTION_TRACKING_INTERVAL])
            ->andFilterWhere(['!=', 'brand_id', self::TESTING_MERCHANT_ID])
            ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id', 'brand_id'])
            ->all();

        foreach ($transactionsSample as $item) {
            \Yii::$app->sender->send([
                'Failed transaction id: ' . $item->id . ';',
                'Merchant transaction id: ' . $item->merchant_transaction_id . ';',
                'Brand id: ' . $item->brand_id . ';',
                'Time: ' . date('m-d-Y h:i:s', $item->updated_at) . ';',
                'Currency: ' . $item->currency . ';',
                'Status: ' . \pps\payment\Payment::getStatusDescription($item->status) . ';',
                'Payment system: ' . $item->paymentSystem->name . '.'
            ]);
        };
    }

    public function actionPaymentSystem()
    {
        $inquirer = \Yii::$app->inquirer;
        $sender = \Yii::$app->sender;
        $notRespondedPaymentSystems = $inquirer->getNotRespondedPaymentSystems();
        if ($notRespondedPaymentSystems) {
            foreach ($notRespondedPaymentSystems as $ps) {
                $sender->send([
                    'Not responded Payment system: ' . $ps['name'] . '.'
                ]);
            }
        }
    }
}