<?php


namespace common\components\inquirer;

use backend\models\PaymentSystemStatus;
use backend\models\Settings;
use common\components\helpers\Restructuring;
use yii\db\Query;
use pps\querybuilder\QueryBuilder;
use pps\querybuilder\src\IQuery;

class PaymentSystemInquirer
{
    const STATUS_FAILED = 500;
    const STATUS_PAYMENT_ERROR = 422;
    const ERROR_NETWORK = 'Network Error!';
    const PAYMENT_SYSTEM_ACTIVITY = 1;
    const QUERY_URL = 'http://master.api.paygate.xim.hattiko.pw/merchant/';


    /**
     * @return array
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function getNotRespondedPaymentSystems(): array
    {
        $paymentSystemsStatuses = PaymentSystemStatus::find()->indexBy('payment_system_id')->all();
//        TO DO: throw exceptions for Settings::getValue create my own exception class
        $testingMerchantId = Settings::getValue('testing_merchant_id');

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
            ->where(['payment_system.active' => self::PAYMENT_SYSTEM_ACTIVITY])
            ->andWhere(['user_payment_system.node_id' => $testingMerchantId]);

        $command = $query->createCommand(\Yii::$app->db2);
        $paymentSystemsPpsSample = $command->queryAll();
        $paymentSystemsPpsData = $this->sortPaymentSystemsDataSort($paymentSystemsPpsSample);

        $notRespondingPaymentSystems = [];
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

                $name = $data['name'];
                $active = 1;
                if ($data['payment_system']
                    && $data['currency']
                    && $data['amount']
                    && $data['payment_method']
                    && $data['way']) {
                    $response = $this->sendQuery($data);
                    /**
                     * @var $response pps\querybuilder\src\Query
                     */
                    $httpCode = $response->getInfo()['http_code'] ?? '';

                    if ($httpCode === self::STATUS_FAILED) {
                        $active = 0;
                    } else if ($httpCode === self::STATUS_PAYMENT_ERROR) {
                        $responseDecoded = $response->getResponse(true);
                        if (($errors = $responseDecoded['errors'])) {
                            foreach ($errors as $error) {
                                if ($error['message'] === self::ERROR_NETWORK) {
                                    $active = 0;
                                }
                            }
                        }
                    }
                } else {
                    $active = 2;
                }

                $paymentSystemStatus->active = $active;
                $paymentSystemStatus->name = $name;
                $paymentSystemStatus->payment_system_id = $id;
                $paymentSystemStatus->deleted = 0;

                if ($paymentSystemStatus->save()) {
                    $transaction->commit();
                    if ($active != 1) {
                        $notRespondingPaymentSystems[$id] = $paymentSystemStatus;
                    }

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

        if (!empty($paymentSystemsStatuses)) {
            foreach ($paymentSystemsStatuses as $data) {
                $data->deleted = 1;
                $data->save();
            }
        }
        return $notRespondingPaymentSystems;
    }

    /**
     * @param $paymentSystemsPpsSample
     * @return array
     * Sorting data for each payment system and getting fields necessary for transaction request
     */
    private function sortPaymentSystemsDataSort(array $paymentSystemsPpsSample): array
    {
        $paymentSystemsPpsData = [];
        foreach ($paymentSystemsPpsSample as $item) {
            if (!$item['payment_system_id']) {
                continue;
            }
            $interim = [];
            $id = $item['payment_system_id'];
            $interim['amount'] = 1;
            $interim['name'] = $item['name'];
            $interim['payment_system'] = $item['code'];

            if ($item['currencies'] && !empty($item['currencies'])) {
                $currenciesArr = json_decode($item['currencies']);
                if ($currenciesArr[0] ?? null) {
                    $currencyArr = explode('_', $currenciesArr[0]);
                    if ($currencyArr[2]) {
                        $interim['way'] = $currencyArr[2];
                    }
                }
            }
            $fieldsArray = null;
            if ($way = $interim['way'] ?? null) {
                /**
                 * @var $fieldsArray array regulated data needed for committal transaction
                 */
                $fieldsArray = $this->getPaymentSystemData($item['code'], $way);
            }

            if ($fieldsArray && $fieldsArray[0]) {
                if ($fieldsArray[0]['currency'] && !empty($fieldsArray[0]['currency'])) {
                    $interim['currency'] = $fieldsArray[0]['currency'];
                }
                if ($fieldsArray[0]['methods'] && !empty($fieldsArray[0]['methods'])
                    && $fieldsArray[0]['methods'][0] && !empty($fieldsArray[0]['methods'][0])
                    && $fieldsArray[0]['methods'][0]['method'] && !empty($fieldsArray[0]['methods'][0]['method'])
                ) {
                    $interim['payment_method'] = $fieldsArray[0]['methods'][0]['method'];
                    if ($fieldsArray[0]['methods'][0]['fields'] && !empty($fieldsArray[0]['methods'][0]['fields'])) {
                        $fields = $fieldsArray[0]['methods'][0]['fields'];
                        $fieldsSorted = [];
                        foreach ($fields as $key => $value) {
                            $fieldsSorted[$value] = '';
                        }
                        $interim['requisites'] = $fieldsSorted;
                    }
                }
            }
            $paymentSystemsPpsData[$id] = $interim;
        }

        return $paymentSystemsPpsData;
    }

    /**
     * @param string $code
     * @param bool $way
     * @return array
     * Returns fields array (contains transaction request fields for enabled methods)
     * leaded to the canonical form
     */
    public function getPaymentSystemData(string $code, bool $way)
    {
        /**
         * @var $query pps\querybuilder\src\IQuery
         * Request to pps api. Documentation http://master.frontend.paygate.xim.hattiko.pw/site/api-doc (api-info)
         */
        $query = $this->query('api-info', ['code' => $code], false);
        /**
         * @var $accountInfo array
         * Request to pps api. Documentation http://master.frontend.paygate.xim.hattiko.pw/site/api-doc (account-info)
         */
        $accountInfo = $this->query('account-info', [], false)->getResponse(true);
        /**
         * @var $enabledMethods
         * Contains data (including transaction type and method) for each payment system
         * enshrined and enabled for the current merchant
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
         * @var $ps array
         * @var $query pps\querybuilder\src\IQuery
         * Payment system fields from file present in the payment systems
         */
        $ps = $query->getResponse(true);
        $flag = 0;
        if (empty($ps)) {
            $flag = 4;
        }
        $counter = 0;
        $counterEmptyVal = 0;
        foreach ($ps as $key => $val) {
            $lowerKey = strtolower($key);
            /**
             * @var $lowerKey string. This is Payment systems method
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
                if (($k == 'deposit' && empty($v)) || ($k == 'withdraw' && empty($v))) {
                    $counterEmptyVal++;
                }
            }
            if (isset($accountMethods[$lowerKey])) {
                $flag = 1;
                break;
            }
        }

        if ($counter == $counterEmptyVal) {
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
     * @param array $ps
     * @param array $accountMethods
     * @param string $way
     * @return array
     *
     */
    private function currenciesApiInfoKey(array $ps, array $accountMethods, string $way): array
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
    private function restructuringPs(array $ps, int $flag, array $accountMethods): array
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
        return [];
    }

    /**
     * @param array $query
     * @param string $date
     * @param bool $post
     * @return string
     * Generate AuthKey by pps api.
     * Url: http://master.frontend.paygate.xim.hattiko.pw/site/api-doc
     * endpoint: Auth
     */
    private function genAuthKey(array $query, string $date, bool $post): string
    {
        //        TO DO: throw exceptions for Settings::getValue
        $publicKey = Settings::getValue('publicKey');
        $privateKey = Settings::getValue('privateKey');
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
     * @return IQuery
     * Helper method for making a request to the pps api
     */
    private function query(string $endpoint, array $data = [], $isPost = true): IQuery
    {
        $url = self::QUERY_URL . $endpoint;

        $date = date('U');
        $authKey = $this->genAuthKey($data, $date, $isPost);

        $query = (new QueryBuilder($url))
            ->setParams($data)
            ->setHeader('Auth', $authKey)
            ->setHeader('X-PPS-Time', $date);

        if ($isPost) {
            $query->asPost()->json();
        }
        return $query->send();
    }

    /**
     * @param $request
     * @return array|IQuery
     * Sends final request to the payment system
     */
    public function sendQuery($request): IQuery
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
    }
}