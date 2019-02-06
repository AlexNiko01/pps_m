<?php

namespace pps\testps;

use api\classes\ApiError;
use common\models\Transaction;
use pps\payment\Payment;

use pps\payment\TypeFactory;
use yii\base\InvalidParamException;
use yii\db\Exception;

use yii\helpers\Html;
use yii\web\Response;

/**
 * Class TestPS
 * @package pps\testps
 */
class TestPS extends Payment
{
    /**
     * @var string
     */
    private $_example_key;
    /**
     * @var Transaction
     */
    private $_transaction;

    /**
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['example_key'])) {
            throw new InvalidParamException('example_key empty');
        }

        $this->_example_key = $data['example_key'];
    }

    /**
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        $validate = static::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], 'deposit');

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }
        sleep(1);

        return [
            'data' => [
                'fields' => [],
                'amount' => $params['amount'],
                'buyer_write_off' => 0,
                'merchant_refund' => 0,
                'currency' => $params['currency'],
            ]
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        $this->_transaction = $params['transaction'];

        $validate = static::validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, 'deposit');

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        try {
            $this->_transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'transaction_id is not unique'
            ];
        }

        $this->_transaction->external_id = $this->_transaction->id;

        $this->_transaction->status = self::STATUS_CREATED;
        $this->_transaction->refund = 0;
        $this->_transaction->write_off = 0;
        $this->_transaction->save(false);

        $this->receive([
            'transaction' => $this->_transaction,
            'receive_data' => [
                'amount' => $this->_transaction->amount,
                'currency' => $this->_transaction->currency,
                'status' => self::STATUS_SUCCESS,
                'fields' => array_merge([
                    'ps_name' => 'John',
                    'ps_email' => 'john1990@mail.com',
                    'ps_field' => 'Just string',
                ], json_decode($this->_transaction->requisites, true))
            ],
        ]);

        if ($this->_transaction->comment === 'offline') {
            $html = '<h3 style="text-align: center;">Offline method of payment system for testing</h3>';
            $html .= '<div style="text-align: center;">';
            $html .= Html::img('https://loremflickr.com/320/240');
            $html .= "</p>";

            $answer['redirect'] = [
                'method' => 'OFFLINE',
                'url' => null,
                'params' => $html,
            ];

        } elseif ($this->_transaction->comment === 'post') {
            $answer['redirect'] = [
                'method' => 'POST',
                'url' => rtrim(\Yii::$app->params['frontend_link'] ?? '', '/') . '/site/success?method=post',
                'params' => [],
            ];
        } else {
            $answer['redirect'] = [
                'method' => 'GET',
                'url' => rtrim(\Yii::$app->params['frontend_link'] ?? '', '/') . '/site/success',
                'params' => [
                    'method' => 'get'
                ],
            ];
        }

        $answer['data'] = $this->_transaction::getDepositAnswer($this->_transaction);

        return $answer;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function receive(array $data): bool
    {
        $transaction = $data['transaction'];
        $receiveData = $data['receive_data'];

        if (in_array($transaction->status, Payment::getFinalStatuses())) {

            return true;
        }

        $need = ['amount', 'currency', 'status'];

        if (!self::checkRequiredParams($need, $receiveData)) {
            return false;
        }

        // If amounts not equal

        if ($transaction->amount != $receiveData['amount']) {
            $this->logAndDie('TestPS receive() transaction amount not equal received amount',
                "Transaction amount = {$transaction->amount}\nreceived amount = {$receiveData['amount']}",
                "Transaction amount not equal received amount");
        }

        // If different currency

        if ($transaction->currency != $receiveData['currency']) {
            $this->logAndDie('TestPS receive() different currency',
                "Merchant currency = {$transaction->currency}\nreceived currency = {$receiveData['currency']}",
                "Different currency");
        }

        $typeFactory = new TypeFactory($receiveData['fields']);
        TypeFactory::addAlias('method', MethodType::class);
        $type = $typeFactory->getInstance($transaction->payment_method, [
            'ps_name' => 'name',
            'ps_email' => 'email',
        ]);

        $transaction->callback_data = json_encode([$transaction->payment_method => $type->getFieldsWithUndefined()]);

        $transaction->status = $receiveData['status'];
        $transaction->save(false);

        return true;
    }

    /**
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params)
    {
        $validate = static::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], 'withdraw');

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        return [
            'data' => [
                'fields' => [],
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_write_off' => 0,
                'buyer_receive' => 0,
            ]
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    public function withDraw(array $params)
    {
        $this->_transaction = $params['transaction'];

        $validate = static::validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, 'withdraw');

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        try {
            $this->_transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $answer = [];

        $this->_transaction->external_id = $this->_transaction->id;
        $this->_transaction->receive = 0;

        $this->_transaction->write_off = 0;
        $this->_transaction->status = self::STATUS_SUCCESS;
        $this->_transaction->save(false);

        $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        switch (strtolower($this->_transaction->comment)) {
            case 'success':
                $this->_transaction->status = self::STATUS_SUCCESS;
                break;
            case 'error':
                $this->_transaction->status = self::STATUS_ERROR;
                break;
            case 'cancel':
                $this->_transaction->status = self::STATUS_CANCEL;
                break;
            case 'timeout':
                sleep(5);
                $this->_transaction->status = self::STATUS_TIMEOUT;
                $params['updateStatusJob']->transaction_id = $this->_transaction->id;
                break;
            default:
                $this->_transaction->status = self::STATUS_PENDING;
                $params['updateStatusJob']->transaction_id = $this->_transaction->id;
        }

        $this->_transaction->save(false);

        $answer['data'] = [
            'id' => $this->_transaction->id,
            'transaction_id' => $this->_transaction->merchant_transaction_id,
            'status' => $this->_transaction->status,
            'buyer_receive' => $this->_transaction->receive,
            'amount' => $this->_transaction->amount,
            'currency' => $this->_transaction->currency,
        ];

        return $answer;
    }

    /**
     * @param object $transaction
     * @param null $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if ($transaction->status == self::WAY_WITHDRAW && !in_array($transaction->status, self::getFinalStatuses())) {

            $nTimes = (int)$transaction->amount;

            if ($nTimes > 1) {
                if (empty($transaction->result_data)) {
                    $transaction->result_data = 1;
                } else {
                    $transaction->result_data += 1;
                }

                $transaction->save(false);

                if ($nTimes > $transaction->result_data) {
                    $transaction->status = self::STATUS_PENDING;
                    $transaction->save(false);
                    return false;
                }
            }

            $randNumber = rand(0, 100);

            if (0 <= $randNumber && $randNumber < 75) {
                $transaction->status = self::STATUS_SUCCESS;
            } else {
                $transaction->status = self::STATUS_ERROR;
            }

            $transaction->save(false);
        }

        return true;
    }

    /**
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        return [
            'CUR' => [
                'method' => [
                    'code' => 'method',
                    'name' => 'Method',
                    'min' => 1,
                    'max' => 30,
                    'deposit' => true,
                    'withdraw' => true
                ]
            ],
            'REN' => [
                'method' => [
                    'code' => 'method',
                    'name' => 'Method',
                    'min' => 1,
                    'max' => 20,
                    'deposit' => true,
                    'withdraw' => true
                ]
            ]
        ];
    }

    /**
     * @return \yii\base\Model
     */
    public static function getModel():\yii\base\Model
    {
        return new Model();
    }

    /**
     * @param string $currency
     * @param string $method
     * @param float $amount
     * @param string $way
     * @return bool|string
     */
    public static function validateTransaction(string $currency, string $method, float $amount, string $way)
    {
        $currencies = static::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        if (!isset($currencies[$currency][$method])) {
            return "Method '{$method}' does not exist";
        }

        $method = $currencies[$currency][$method];

        if (!isset($method[$way]) || !$method[$way]) {
            return "Payment system does not support '{$way}'";
        }

        if (isset($method['min']) && $method['min'] > $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
        if (isset($method['max']) && $method['max'] <= $amount) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;

        if ($method['min'] > $amount || $method['max'] < $amount) {
            return "Amount should to be more than '{$method['min']}' and less than '{$method['max']}'";
        }

        return true;
    }

    /**
     * @param string $paymentMethod
     * @param string $currency
     * @return string
     */
    public static function transformPaymentMethod(string $paymentMethod, string $currency)
    {
        return 'method';
    }

    /**
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return [];
    }

    /**
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['tr_id'] ?? 0;
    }

    /**
     * @return string
     */
    public static function getSuccessAnswer()
    {
        return 'DONE';
    }

    /**
     * @return string
     */
    public static function getResponseFormat()
    {
        return Response::FORMAT_HTML;
    }
}