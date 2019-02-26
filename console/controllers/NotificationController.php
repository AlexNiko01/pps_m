<?php

namespace console\controllers;

use backend\models\PaymentSystemStatus;
use common\components\helpers\Logger;
use common\components\helpers\Restructuring;
use common\models\Transaction;
use pps\querybuilder\QueryBuilder;
use yii\console\Controller;
use yii\db\Query;
use yii\web\Response;


class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 60;

    /**
     * Action for checking failed transaction and sending notification
     */
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

    public function actionPaymentSystemData($code, $way)
    {
        /**
         * from file
         */
        $query = $this->query('api-info', ['code' => $code], false);
        /**
         * from DB
         */
        $accountInfo = $this->query('account-info', [], false)->getResponse(true);
        /**
         * from DB
         */
        $enabledMethods = $accountInfo['payment_systems'];
        /**
         * @var $accountMethods array. Keeping current Payment systems methods
         */
        $accountMethods = null;

        foreach ($enabledMethods as $enabledMethod) {
            if ($enabledMethod['code'] == $code) {
                $accountMethods = $enabledMethod['currencies'];
            }
        }
        /**
         * from file:
         */
        $ps = $query->getResponse(true);

        $flag = 0;

        if (empty($ps)) {
            $flag = 4;
        }

        $counter = 0;
        $counter_empty_val = 0;
        foreach ($ps as $key => $val) {
            $lowerKey = strtolower($key);
            /**
             * @var $lowerKey string. This is Payment systems method!
             */
            if ($lowerKey == 'bitcoin') {
                $flag = 3;
                break;
            }
            /**
             * @var $val array. Contains field for current transaction payment system
             */
            foreach ($val as $k => $v) {
                $counter++;
                $lK = strtolower($k);
                if (isset($accountMethods[$lK])) {
                    $flag = 2;
                    break;
                }
                /**
                 * @var $k string. Type of transaction: deposit or withdraw
                 * @var $v string. Field for transaction request
                 */
                if (($k == "deposit" && empty($v)) || ($k == "withdraw" && empty($v))) {
                    $counter_empty_val++;
                }
            }
            if (isset($accountMethods[$lowerKey])) {
                $flag = 1;
                break;
            }
        }

        if ($counter == $counter_empty_val) {
            $flag = 4;
        }

        if ($flag == 1) {

            return $this->currenciesApiInfoKey($ps, $accountMethods, $way);

        } else {
            $newPs = $this->restructuringPs($ps, $flag, $accountMethods);
        }

        return $this->currenciesApiInfoKey($newPs, $accountMethods, $way);
    }

    /**
     * @param $ps
     * @param $accountMethods
     * @param $way
     * @return array
     */
    private function currenciesApiInfoKey($ps, $accountMethods, $way)
    {

        $currencies = [];

        foreach ($ps as $key => $methods) {

            $c = strtolower($key);
            if (empty($accountMethods[$c])) continue;

            $currency = [
                'currency' => $key,
                'methods' => [],
            ];

            foreach ($methods as $method => $fields) {
                if ($way == 'deposit' && !isset($accountMethods[$c][$method]['way']['deposit'])) {
                    continue;
                }
                if ($way == 'withdraw' && !isset($accountMethods[$c][$method]['way']['withdraw'])) continue;

                $m = [
                    'method' => $method,
                    'fields' => []
                ];

                foreach ($fields[$way] ?? [] as $field => $_) {
                    $m['fields'][] = $field;
                }

                $currency['methods'][] = $m;
            }

            $currencies[] = $currency;
        }

        return $currencies;
    }

    /**
     * @param $ps
     * @param $flag
     * @param $accountMethods
     * @return array
     */
    private function restructuringPs($ps, $flag, $accountMethods)
    {
        if ($flag == 0) {
            return Restructuring::firstCategory($ps);
        } elseif ($flag == 2) {
            return Restructuring::secondCategory($ps);
        } elseif ($flag == 3) {
            return Restructuring::thirdCategory($ps, $accountMethods);
        } elseif ($flag == 4) {
            return Restructuring::forEmptyPs($accountMethods);
        }
    }

    /**
     * @param $paymentSystemsPpsSample
     * @return array
     */
    private function sortPaymentSystemsDataSort($paymentSystemsPpsSample)
    {
        $paymentSystemsPpsData = [];
        foreach ($paymentSystemsPpsSample as $item) {
            if ($item['currencies'] === null || !$item['payment_system_id']) {
                continue;
            }

            $id = $item['payment_system_id'];
            $paymentSystemsPpsData[$id]['amount'] = 1;
            $paymentSystemsPpsData[$id]['name'] = $item['name'];
            $paymentSystemsPpsData[$id]['payment_system'] = $item['code'];
            $paymentSystemsPpsData[$id]['way'] = '';

            if ($item['currencies'] && !empty($item['currencies'])) {
                $currenciesArr = json_decode($item['currencies']);
                if ($currenciesArr[0]) {
                    $currencyArr = explode('_', $currenciesArr[0]);
                    if ($currencyArr[2]) {
                        $paymentSystemsPpsData[$id]['way'] = $currencyArr[2];
                    }
                }
            }
            $fieldsArray = $this->actionPaymentSystemData($item['code'], $paymentSystemsPpsData[$id]['way']);

//            TODO: check array sorting

            if ($fieldsArray[0]) {
                if ($fieldsArray[0]['currency'] && !empty($fieldsArray[0]['currency'])) {
                    $paymentSystemsPpsData[$id]['currency'] = $fieldsArray[0]['currency'];
                }
                if ($fieldsArray[0]['methods'] && !empty($fieldsArray[0]['methods'])
                    && $fieldsArray[0]['methods'][0] && !empty($fieldsArray[0]['methods'][0])
                    && $fieldsArray[0]['methods'][0]['method'] && !empty($fieldsArray[0]['methods'][0]['method'])
                ) {
                    $paymentSystemsPpsData[$id]['payment_method'] = $fieldsArray[0]['methods'][0]['method'];
                    if ($fieldsArray[0]['methods'][0]['fields'] && !empty($fieldsArray[0]['methods'][0]['fields'])) {
                        $fields = $fieldsArray[0]['methods'][0]['fields'];
                        $fieldsSorted = [];
                        foreach ($fields as $key => $value) {
                            $fieldsSorted[$value] = '';
                        }
                        $paymentSystemsPpsData[$id]['requisites'] = $fieldsSorted;
                    }
                }
            }
        }
        return $paymentSystemsPpsData;
    }


    /**
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
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
            ->where(['payment_system.active' => 1])
            ->andWhere(['user_payment_system.node_id' => 5]);

        $command = $query->createCommand(\Yii::$app->db2);
        $paymentSystemsPpsSample = $command->queryAll();

        $paymentSystemsPpsData = $this->sortPaymentSystemsDataSort($paymentSystemsPpsSample);

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
                $requestData = $data;
                unset($requestData['name']);


                if ($requestData['payment_system'] && $requestData['currency'] && $requestData['payment_method'] && $requestData['way']) {

                    $response = $this->actionSendQuery($requestData);
                    /**
                     * @var $response pps\querybuilder\src\Query
                     */
                    $httpCode = $response->getInfo()['http_code'] ?? '';

                    if ($httpCode && $httpCode < 400) {
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

    public function actionSendQuery($request)
    {
        if ($request['way'] == 'deposit') {
            $path = 'deposit';
        } elseif ($request['way'] == 'withdraw') {
            $path = 'withdraw';
        } else {
            return ['error' => 'Incorrect way'];
        }

        unset($request['way']);
        unset($request['_csrf']);

        $request['transaction_id'] = 'TA_' . date('mdGis') . rand(10, 99);
        $query = $this->query($path, $request, true);
        return $query;
        $response = $query->getResponse(true);

        if (isset($response['errors'])) {
            return [
                'status' => 'error',
                'data' => $response['errors']
            ];
        } elseif (isset($response['data'])) {
            return [
                'status' => 'success',
                'data' => $response['data']
            ];
        }

        return [
            'status' => 'error',
            'data' => $query->getInfo()
        ];
    }

}