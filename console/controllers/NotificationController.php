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

    private function sortPaymentSystemsData($paymentSystemsPpsSample)
    {
        $paymentSystemsPpsData = [];
        foreach ($paymentSystemsPpsSample as $item) {
            if ($item['currencies']) {
                $currencies = json_decode($item['currencies']);
                if ($currencies[0] && $item['payment_system_id']) {
                    $id = $item['payment_system_id'];
                    $currenciesArr = explode('_', $currencies[0]);
                    $paymentSystemsPpsData[$id]['currency'] = $currenciesArr[0];
                    $paymentSystemsPpsData[$id]['payment_method'] = $currenciesArr[1];
                    $paymentSystemsPpsData[$id]['payment_system'] = $item['code'];
                    $paymentSystemsPpsData[$id]['way'] = $currenciesArr[2];
                }
            }
        }
        return $paymentSystemsPpsData;
    }

    public function actionPs()
    {

        $paymentSystemsStatuses = PaymentSystemStatus::find()->indexBy('payment_system_id')->all();

        $query = new Query;
        $query->select([
            'user_payment_system.payment_system_id',
            'user_payment_system.currencies',
            'payment_system.name',
            'payment_system.code'
        ])
            ->from('user_payment_system')
            ->leftJoin('payment_system',
                'payment_system.id =user_payment_system.payment_system_id'
            )
            ->where(['payment_system.active' => 1,])
            ->andWhere(['user_payment_system.node_id' => 5,])
            ->indexBy(function ($row) {
                return $row['user_payment_system.payment_system_id'];
            });

        $command = $query->createCommand(\Yii::$app->db2);
        $paymentSystemsPpsSample = $command->queryAll();

        $paymentSystemsPpsData = $this->sortPaymentSystemsData($paymentSystemsPpsSample);

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

                $httpCode = $this->sendRequest($data);
                var_dump($httpCode);


                if ($httpCode && $httpCode < 400) {
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

    /**
     * @param string $endpoint
     * @param array $data
     * @param bool $isPost
     * @return \pps\querybuilder\src\IQuery
     */
    private function query(string $endpoint, array $data = [], $isPost = true)
    {

        $url = 'http://master.api.paygate.xim.hattiko.pw/merchant/' . $endpoint;

        $credentials = [
            'publicKey' => 'qlZTm0U6YvF0QeEZ',
            'privateKey' => 'pgweRDPuTssaDtwBI5EotpfZHw3hdYaY'
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


    public function sendRequest($data)
    {
        $request = [
            'payment_system' => $data['payment_system'],
            'currency' => $data['currency'],
            'amount' => '1',
            'payment_method' => $data['payment_method'],
            'transaction_id' => 'TA_' . date('mdGis') . rand(10, 99) . rand(1, 4),
            'way' => $data['way']
        ];
        $path = $data['way'];

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
        return [
            'httpCode' => $httpCode,
            'result' => $result
        ];

    }

}