<?php

namespace console\controllers;

use backend\models\PaymentSystemStatus;
use common\components\helpers\Logger;
use common\models\Transaction;
use GuzzleHttp\Exception\GuzzleException;
use pps\querybuilder\QueryBuilder;
use yii\console\Controller;
use yii\db\Query;
use yii\web\Response;


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

        $enabledMethods = $this->actionPaymentSystemData();
        foreach ($enabledMethods as $m){
            var_dump($m['currencies']);
            die();
        }


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

                    $request = [
                        'payment_system' => $code,
                        'currency' => 'USD',
                        'amount' => '1',
//                        TODO:   figure out what is payment_method:
                        'payment_method' => 'w1',
                        'transaction_id' => 'TA_02199033163',
                        'way' => 'withdraw'
                    ];
                    $path = 'withdraw';

                    $query = $this->query($path, $request, true);
                    $response = $query->getResponse(true);

                    if (isset($response['errors'])) {
                        $result = [
                            'status' => 'error',
                            'data' => $response['errors']
                        ];
                    } elseif (isset($response['data'])) {
                        $result = [
                            'status' => 'success',
                            'data' => $response['data']
                        ];
                    } else {
                        $result = [
                            'status' => 'error',
                            'data' => $query->getInfo()
                        ];
                    }

                    $httpCode = $query->getInfo()['http_code'];
                    var_dump($result, $httpCode);


                    if ($httpCode < 400) {
                        $active = 1;
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



    /**
     * @param array $credentials
     * @param array $query
     * @param string $date
     * @param bool $post
     * @return string
     */
    private function genAuthKey(array $credentials, array $query, string $date, bool $post): string
    {
        $publicKey = $credentials['publicKey'];
        $privateKey = $credentials['privateKey'];

        $flags = 0;

        if ($post) {
            $method = 'post';
            $contentType = 'application/json';
        } else {
            $method = 'get';
            $contentType = '';
            $flags |= JSON_NUMERIC_CHECK;
        }

        ksort($query, SORT_STRING);

        $contentStringMD5 = md5(json_encode($query, $flags));

        $stringToSign = "$method\n";
        $stringToSign .= "$contentStringMD5\n";
        $stringToSign .= "$contentType\n";
        $stringToSign .= "$date";

        $signature = base64_encode(hash_hmac('sha256', utf8_encode($stringToSign), $privateKey, true));

        return "PPS {$publicKey}:{$signature}";
    }

    private function query(string $endpoint, array $data = [], $isPost = true)
    {

        $url = 'https://api.paypro.pw/merchant/' . $endpoint;

        $credentials = [
            'publicKey' => 'Tg8lP56esqmFS3aW',
            'privateKey' => 'Nhx9M7bREGuteqz8GIuvOBKg16VTa5QX'
        ];

        $date = date('U');
        $authKey = $this->genAuthKey($credentials, $data, $date, $isPost);

        $query = (new QueryBuilder($url))
            ->setParams($data)
            ->setHeader('Auth', $authKey)
            ->setHeader('X-PPS-Time', $date);

        if ($isPost) {
            $query->asPost()->json();
        }

        return $query->send();
    }

    public function actionPaymentSystemData()
    {

        $accountInfo = $this->query('account-info', [], false)->getResponse(true);
        $enabledMethods = $accountInfo['payment_systems'];

        return $enabledMethods;
    }
}