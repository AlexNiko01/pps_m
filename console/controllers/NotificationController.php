<?php

namespace console\controllers;

use backend\models\PaymentSystem;
use backend\models\PaymentSystemExternalData;
use backend\models\PaymentSystemStatus;
use common\components\helpers\Logger;
use common\models\Transaction;
use GuzzleHttp\Exception\GuzzleException;
use Mpdf\Tag\P;
use yii\console\Controller;
use yii\db\Query;


class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 60;


    public function actionTransaction(): void
    {
        $transactionsSample = Transaction::find()
            ->filterWhere(['not in', 'status', [0, 1, 2]])
            ->andFilterWhere(['>', 'updated_at', time() - self::TRANSACTION_TRACKING_INTERVAL])
            ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id'])
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
        Logger::recodeLog('test');

    }

    public function actionPs()
    {
        $paymentSystemsStatuses = PaymentSystemStatus::find()->indexBy('payment_system_id')->all();

        $subQuery = (new Query)
            ->select('payment_system_id')
            ->from('payment_system_external_data')
            ->distinct();
        $paymentSystemsPpsData = (new Query())
            ->select(['name', 'id', 'code'])
            ->from('payment_system')
            ->where(['in', 'id', $subQuery])
            ->andWhere(['active' => 1])
            ->indexBy('id')
            ->all(\Yii::$app->db2);

        foreach ($paymentSystemsPpsData as $id => $data) {
            $connection = \Yii::$app->db;
            $transaction = $connection->beginTransaction();
            try {
                if (array_key_exists($id, $paymentSystemsStatuses)) {
                    $paymentSystemStatus = $paymentSystemsStatuses[$id];
                    unset($paymentSystemsStatuses[$id]);
                } else {
                    $paymentSystemStatus = new PaymentSystemStatus();
                }

                $active = 0;
                $code = $data['code'];
                $url = \Yii::$app->pps->payments[$code] ? \Yii::$app->pps->payments[$code]['url'] : '';
                $method = \Yii::$app->pps->payments[$code] ? \Yii::$app->pps->payments[$code]['method'] : '';
                if ($url && $method) {
                    $response = $this->sendRequest($url, $method);
                    if ($response) {
                        if ($response->getStatusCode() < 400) {
                            $active = 1;
                        }
                    }
                    $paymentSystemStatus->active = $active;
                    $paymentSystemStatus->name = $data['name'];
                    $paymentSystemStatus->payment_system_id = $id;
                    $paymentSystemStatus->deleted = $id;

                    if ($paymentSystemStatus->save()) {
                        $transaction->commit();
                    } else {
                        $transaction->rollBack();
                    }
                }

            } catch (\Exception $e) {
                $transaction->rollBack();
                var_dump($e->getMessage());
                throw $e;
            } catch (\Throwable $e) {
                $transaction->rollBack();
                var_dump($e->getMessage());
                throw $e;
            }
        }

        if (!empty($paymentSystemsPps)) {
            foreach ($paymentSystemsPps as $pss) {
                $ps = $paymentSystemsStatuses[$pss['id']];
                $ps->deleted = 1;
                $ps->save();
            }
        }
    }

    private function sendRequest($url, $method)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request($method, $url);
        } catch (GuzzleException $e) {
        }
        return $response;
    }

}