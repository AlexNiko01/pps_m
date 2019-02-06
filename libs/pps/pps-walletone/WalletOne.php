<?php

namespace pps\walletone;

use api\classes\ApiError;
use \pps\payment\Payment;
use Yii;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Class WalletOne
 * @package pps\walletone
 */
class WalletOne extends Payment
{
    const SCI_URL = 'https://wl.walletone.com/checkout/checkout/Index';
    const HiD = 24; // Hours in day
    const MiH = 60; // Minutes in an hour
    const SiM = 60; // Seconds in a minute

    /**
     * @var object
     */
    private $_transaction;
    /**
     * @var object
     */
    private $_requests;
    /**
     * @var object
     */
    private $_receive;
    /**
     * @var string
     */
    private $_merchant_id;
    /**
     * @var string
     */
    private $_secret_key;
    /**
     * @var integer
     */
    private $_expired_date = 7;

    /**
     * Fill the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (!$data['merchant_id']) {
            throw new InvalidParamException('merchant_id empty');
        }
        if (!$data['secret_key']) {
            throw new InvalidParamException('secret_key empty');
        }

        $this->_merchant_id = $data['merchant_id'];
        $this->_secret_key = $data['secret_key'];

        if (!empty($data['expired_date'])) {
            if (intval($data['expired_date']) > 0) {
                if (intval($data['expired_date']) > 30) {
                    $data['expired_date'] = 30;
                }

                $this->_expired_date = intval($data['expired_date']);
            }
        }
        // convert expired_date from count date to count seconds
        $this->_expired_date = $this->_expired_date * static::HiD * static::MiH * static::SiM;
    }

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        $validate = static::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        return [
            'data' => [
                'fields' => static::getFields($params['currency'], $params['payment_method'], self::WAY_DEPOSIT),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_refund' => null,
                'buyer_write_off' => null,
            ]
        ];
    }

    /**
     * Invoice for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        $this->_transaction = $params['transaction'];
        $this->_requests = $params['requests'];

        $validate = static::validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, self::WAY_DEPOSIT);
        $currenciesList = static::getSupportedCurrencies();

        if (!$validate) {
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

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $invoice_amount = $this->_transaction->amount;

        $this->_requests['m_out']->transaction_id = $this->_transaction->id;
        $this->_requests['m_out']->data = $this->_requests['merchant'];
        $this->_requests['m_out']->type = 1;
        $this->_requests['m_out']->save(false);

        $redirectParams = [
            'WMI_MERCHANT_ID' => $this->_merchant_id,
            'WMI_PAYMENT_AMOUNT' => number_format($invoice_amount, 2),
            'WMI_CURRENCY_ID' => $params['currency_iso'],
            'WMI_PAYMENT_NO' => $this->_transaction->id,
            'WMI_DESCRIPTION' => "BASE64:" . base64_encode(self::WAY_DEPOSIT),
            'WMI_PTENABLED' => $currenciesList[$this->_transaction->currency][$this->_transaction->payment_method]['code'],
            'WMI_EXPIRED_DATE' => gmdate("Y-m-d\TH:i:s", time() + $this->_expired_date),
            'WMI_SUCCESS_URL' => $params['success_url'],
            'WMI_FAIL_URL' => $params['fail_url'],
        ];
        $redirectParams['WMI_SIGNATURE'] = $this->_getSign($redirectParams);

        $this->_transaction->query_data = json_encode($redirectParams);

        $this->_requests['out']->transaction_id = $this->_transaction->id;
        $this->_requests['out']->data = $this->_transaction->query_data;
        $this->_requests['out']->type = 2;
        $this->_requests['out']->save(false);

        Yii::info(['query' => $redirectParams, 'result' => []], 'payment-walletone-invoice');

        $this->_transaction->result_data = json_encode([]);

        $this->_requests['pa_in']->transaction_id = $this->_transaction->id;
        $this->_requests['pa_in']->data = $this->_transaction->result_data;
        $this->_requests['pa_in']->type = 3;
        $this->_requests['pa_in']->save(false);

        $this->_transaction->refund = $this->_transaction->amount;
        $this->_transaction->status = self::STATUS_CREATED;
        $this->_transaction->save(false);

        return [
            'redirect' => [
                'method' => 'POST',
                'url' => static::SCI_URL,
                'params' => $redirectParams,
            ],
            'data' => $this->_transaction::getDepositAnswer($this->_transaction),
        ];
    }

    /**
     * Method for receiving and updating statuses
     * @param array $data
     * @return bool
     */
    public function receive(array $data): bool
    {
        $this->_transaction = $data['transaction'];
        $this->_receive = $data['receive_data'];

        if (in_array($this->_transaction->status, self::getFinalStatuses())) {
            // check if transaction was executed
            return true;
        }

        if (!isset($this->_receive['WMI_MERCHANT_ID'])) {
            // If merchant_id not equal
            $this->logAndDie(
                'WalletOne receive() merchant_id is not set',
                'Transaction merchant_id = ' . $this->_merchant_id . "\nreceived merchant_id is not set!",
                "Transaction merchant_id is not set"
            );
        }

        if (!isset($this->_receive['WMI_SIGNATURE'])) {
            // If merchant_id not equal
            $this->logAndDie(
                'WalletOne receive() signature is not set',
                'Transaction signature = ' . $this->_merchant_id . "\nreceived signature is not set!",
                "Transaction signature is not set"
            );
        }

        if (!isset($this->_receive['WMI_PAYMENT_NO'])) {
            // If merchant_id not equal
            $this->logAndDie(
                'WalletOne receive() WMI_PAYMENT_NO is not set',
                "Transaction WMI_PAYMENT_NO is not set!",
                "Transaction WMI_PAYMENT_NO is not set"
            );
        }

        if (!isset($this->_receive['WMI_ORDER_STATE'])) {
            // If merchant_id not equal
            $this->logAndDie(
                'WalletOne receive() WMI_ORDER_STATE is not set',
                "Transaction WMI_ORDER_STATE is not set!",
                "Transaction WMI_ORDER_STATE is not set"
            );
        }

        if ($this->_receive['WMI_MERCHANT_ID'] != $this->_merchant_id) {
            // If merchant_id not equal
            $this->logAndDie(
                'WalletOne receive() merchant_id is not equal',
                'Transaction merchant_id = ' . $this->_merchant_id . "\nreceived merchant_id = " . $this->_receive['WMI_MERCHANT_ID'],
                "Transaction merchant_id is not equal"
            );
        }

        if (!$this->checkReceivedSign($this->_receive)) {
            return false;
        }

        if (isset($this->_receive['WMI_ORDER_STATE'])) {
            if ($this->_receive['WMI_ORDER_STATE'] == 'error') {
                // If status not success
                $this->_transaction->status = self::STATUS_ERROR;
                $this->_transaction->save(false);

                return true;
            } elseif (strtoupper($this->_receive['WMI_ORDER_STATE']) != 'ACCEPTED') {
                $this->_transaction->status = self::STATUS_PENDING;
                $this->_transaction->save(false);

                return true;
            }
        }

        if ($this->_transaction->amount != $this->_receive['WMI_PAYMENT_AMOUNT']) {
            // If amounts not equal
            $this->logAndDie(
                'WalletOne receive() transaction amount not equal received amount',
                'Transaction amount = ' . $this->_transaction->amount . "\nreceived amount = " . $this->_receive['WMI_PAYMENT_AMOUNT'],
                "Transaction amount not equal received amount"
            );
        }

        if (strtoupper($data['currency']) != $data['currency']) {
            // If different currency
            $this->logAndDie(
                'WalletOne receive() different currency',
                'Casino currency = ' . $data['currency'] . "\nreceived currency = " . $this->_receive['invoice_currency'] . ' (' . $data['currency'] . ')',
                "Different currency"
            );
        }

        if (isset($this->_receive['WMI_ORDER_ID'])) {
            $this->_transaction->external_id = $this->_receive['WMI_ORDER_ID'];
        }

        $this->_transaction->status = self::STATUS_SUCCESS;
        $this->_transaction->save(false);

        return true;
    }

    /**
     * Check if the seller has enough money.
     * Get required fields for withdraw.
     * This method should be called before withDraw()
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params)
    {
        return [
            'status' => 'error',
            'message' => "Method doesn't supported"
        ];
    }

    /**
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     */
    public function withDraw(array $params)
    {
        return [
            'status' => 'error',
            'message' => "Method doesn't supported"
        ];
    }

    /**
     * Get and update transaction status
     * @param object $transaction
     * @param null|object $model_req
     * @return array
     */
    public function getStatus($transaction, $model_req = null)
    {
        return [
            'status' => 'error',
            'message' => "Method doesn't supported"
        ];
    }

//    /**
//     * Updating not final statuses
//     * @param object $transaction
//     * @param null|object $model_req
//     * @return bool
//     */
//    public function updateStatus($transaction, $model_req = null)
//    {
//    }

    /**
     * Sign function
     * @param array $dataSet
     * @return string
     */
    private function _getSign(array $dataSet): string
    {
        foreach ($dataSet as $name => $val) {
            if (is_array($val)) {
                ksort($val, SORT_STRING);
            }
            $fields[$name] = $val;
        }

        ksort($fields, SORT_STRING);

        $fieldValues = "";

        foreach ($fields as $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $v = iconv("utf-8", "windows-1251", $v);
                    $fieldValues .= $v;
                }
            } else {
                $value = iconv("utf-8", "windows-1251", $value);
                $fieldValues .= $value;
            }
        }

        // Формирование значения параметра WMI_SIGNATURE, путем 
        // вычисления отпечатка, сформированного выше сообщения, 
        // по алгоритму MD5 и представление его в Base64
        return base64_encode(pack("H*", md5($fieldValues . $this->_secret_key)));
    }

    /**
     * @param array $params
     * @return bool
     */
    private function checkReceivedSign(array $params): bool
    {
        $sign = $params['WMI_SIGNATURE'];
        unset($params['WMI_SIGNATURE']);

        $expectedSign = $this->_getSign($params);

        if ($expectedSign != rawurldecode($sign)) {
            Yii::error("WalletOne receive() wrong sign or auth is received: expectedSign = $expectedSign \nSign = $sign", 'payment-walletone');

            return false;
        }

        return true;
    }

    /**
     * Getting model for validation incoming data
     * @return \yii\base\Model
     */
    public static function getModel(): \yii\base\Model
    {
        return new Model();
    }

    /**
     * Validation transaction before send to payment system
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

        if (!isset($currencies[$currency][$method][$way]) || !$currencies[$currency][$method][$way]) {
            return "Payment system does not support '{$way}'";
        }

        if (0 >= $amount) {
            self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            return "Amount should to be more than '0'";
        }

        return true;
    }

    /**
     * Get query for search transaction after success or fail payment
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return isset($data['WMI_PAYMENT_NO']) ? ['id' => $data['WMI_PAYMENT_NO']] : [];
    }

    /**
     * Get transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['WMI_PAYMENT_NO'] ?? 0;
    }

    /**
     * Get success answer for stopping send callback data
     * @return string
     */
    public static function getSuccessAnswer()
    {
        return 'WMI_RESULT=OK';
    }

    /**
     * Get response format for success answer
     * @return string
     */
    public static function getResponseFormat()
    {
        return Response::FORMAT_HTML;
    }

    /**
     * Get fields for filling
     * @param string $currency
     * @param string $way
     * @return array
     */
    private static function getFields(string $currency, string $payment_method, string $way): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency][$payment_method]['fields'][$way] ?? [];
    }

    /**
     * Get sum of commission Cryptonators
     * @param string $currency
     * @param string $method
     * @param float $amount
     * @param string $payer
     * @return float
     */
    private static function getCommission(string $currency, string $method, float $amount, string $payer): float
    {
        $returns = 0;
        $sum_percent = 0;

        $SupportedCurrencies = static::getSupportedCurrencies();

        if (!empty($SupportedCurrencies[$currency][$method]['commission'])) {
            $commissions = $SupportedCurrencies[$currency][$method]['commission'];

            if (floatval($commissions['fix']) > 0) {
                $returns = $commissions['fix'];
            }

            if (floatval($commissions['percent']) > 0) {
                if ($payer == self::COMMISSION_MERCHANT) {
                    $sum_percent = (100 - $commissions['percent']);
                } elseif ($payer == self::COMMISSION_BUYER) {
                    $sum_percent = 100;
                }

                $returns += $amount * $commissions['percent'] / $sum_percent;
            }

            if ($returns > 0) {
                // Перевіряємо чи входить в діапазон комісія (якщо діапазон заданий)
                if (floatval($commissions['min']) > 0) {
                    if ($returns < $commissions['min']) {
                        $returns = $commissions['min'] * $amount / $sum_percent;
                    }
                }

                if (floatval($commissions['max']) > 0) {
                    if ($returns > $commissions['max']) {
                        $returns = $commissions['max'] * $amount / $sum_percent;
                    }
                }
            }

            return round(floatval($returns), 8);
        }

        return 'unknown';
    }

    /**
     * @return array
     */
    public static function getApiFields(): array
    {
        return [
            'walletone' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'yamoney' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'webmoney' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'qiwi' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'okpay' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'beeline' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'mts' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'tele2' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'yota' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'megafon' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'alfaclick' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'tks' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'promsvyazbank' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'sberbank' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'faktura' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'russtand' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'bank-transfer' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'visa' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'mastercard' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'maestro' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'mir' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'bpay' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'smartivi' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'erip' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'easypay' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'privat24' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'kyivstar' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'liqpay' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'google' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'cashu' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'standard-bank' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'setcom-sid' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'testcard' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
        ];
    }

    /**
     * Get supported currencies
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        $fields = static::getApiFields();

        return [
            'RUB' => [
                'walletone' => [
                    'code' => 'WalletOneRUB',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'yamoney' => [
                    'code' => 'YandexMoneyRUB',
                    'name' => 'Яндекс.Гроші',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['yamoney'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'webmoney' => [
                    'code' => 'WebMoneyRUB',
                    'name' => 'WebMoney',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['webmoney'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'qiwi' => [
                    'code' => 'QiwiWalletRUB',
                    'name' => 'Visa Qiwi wallet',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 6,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['qiwi'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'okpay' => [
                    'code' => 'OkpayRUB',
                    'name' => 'Okpay',
                    'fields' => $fields['okpay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'beeline' => [
                    'code' => 'BeelineRUB',
                    'name' => 'Мобильный Платеж Билайн',
                    'fields' => $fields['beeline'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                ],
                'mts' => [
                    'code' => 'MtsRUB',
                    'name' => 'Мобильный Платеж МТС',
                    'fields' => $fields['mts'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                ],
                'tele2' => [
                    'code' => 'Tele2RUB',
                    'name' => 'Мобильный Платеж Tele2',
                    'fields' => $fields['tele2'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                ],
                'yota' => [
                    'code' => 'YotaRUB',
                    'name' => 'Мобильный Платеж Yota',
                    'fields' => $fields['yota'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                ],
                'megafon' => [
                    'code' => 'MegafonRUB',
                    'name' => 'Мобильный Платеж Мегафон',
                    'fields' => $fields['megafon'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                ],
                'alfaclick' => [
                    'code' => 'AlfaclickRUB',
                    'name' => 'Інтернет – банк «Альфа-Клік» («Альфа -Банк»)',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['alfaclick'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                ],
                'tks' => [
                    'code' => 'TinkoffRUB',
                    'name' => 'Tinkoff',
                    'commission' => [
                        'percent' => 2,
                        'fix' => 0,
                        'min' => 2,
                        'max' => 2,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['tks'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                ],
                'promsvyazbank' => [
                    'code' => 'PsbRetailRUB',
                    'name' => 'Інтернет-банк «PSB-Retail» («Промсвязьбанк»)',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['promsvyazbank'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'sberbank' => [
                    'code' => 'SberOnlineRUB',
                    'name' => 'Сбербанк ОнЛ@йн',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['sberbank'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'faktura' => [
                    'code' => 'FakturaruRUB',
                    'name' => 'Faktura.ru',
                    'fields' => $fields['faktura'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'russtand' => [
                    'code' => 'RsbRUB',
                    'name' => 'Интернет-банк Банка «Русский Стандарт»',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['russtand'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'bank-transfer' => [
                    'code' => 'BankTransferRUB',
                    'name' => 'Банківський переказ в рублях',
                    'fields' => $fields['bank-transfer'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'visa' => [
                    'code' => 'CreditCardRUB',
                    'name' => 'VISA',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['visa'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'mastercard' => [
                    'code' => 'CreditCardRUB',
                    'name' => 'MasterCard',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['mastercard'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'maestro' => [
                    'code' => 'CreditCardRUB',
                    'name' => 'Maestro',
                    'fields' => $fields['maestro'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'mir' => [
                    'code' => 'CreditCardRUB',
                    'name' => 'MIR',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['mir'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'testcard' => [
                    'code' => 'TestCardRUB',
                    'name' => 'TestCard',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['testcard'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'ZAR' => [
                'walletone' => [
                    'code' => 'WalletOneZAR',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'setcom-sid' => [
                    'code' => 'SetcomSidZAR',
                    'name' => 'SetcomSid (ЮАР)',
                    'fields' => $fields['setcom-sid'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'standard-bank' => [
                    'code' => 'StandardBankEftZ',
                    'name' => 'Standard Bank EFT (ЮАР)',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['standard-bank'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'USD' => [
                'walletone' => [
                    'code' => 'WalletOneUSD',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'cashu' => [
                    'code' => 'CashUUSD',
                    'name' => 'CashU',
                    'commission' => [
                        'percent' => 5,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['cashu'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'google' => [
                    'code' => 'GoogleWalletUSD',
                    'name' => 'Google Wallet',
                    'fields' => $fields['google'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'okpay' => [
                    'code' => 'OkpayUSD',
                    'name' => 'Okpay',
                    'fields' => $fields['okpay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'bank-transfer' => [
                    'code' => 'BankTransferUSD',
                    'name' => 'Банківський переказ у доларах',
                    'fields' => $fields['bank-transfer'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'visa' => [
                    'code' => 'CreditCardUSD',
                    'name' => 'VISA',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['visa'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'mastercard' => [
                    'code' => 'CreditCardUSD',
                    'name' => 'MasterCard',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['mastercard'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'testcard' => [
                    'code' => 'TestCardUSD',
                    'name' => 'TestCard',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['testcard'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'EUR' => [
                'walletone' => [
                    'code' => 'WalletOneEUR',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'bank-transfer' => [
                    'code' => 'BankTransferEUR',
                    'name' => 'Банківський переказ у євро',
                    'fields' => $fields['bank-transfer'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'visa' => [
                    'code' => 'CreditCardEUR',
                    'name' => 'VISA',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['visa'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'mastercard' => [
                    'code' => 'CreditCardEUR',
                    'name' => 'MasterCard',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['mastercard'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'testcard' => [
                    'code' => 'TestCardEUR',
                    'name' => 'TestCard',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['testcard'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'UAH' => [
                'walletone' => [
                    'code' => 'WalletOneUAH',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'liqpay' => [
                    'code' => 'LiqPayMoneyUAH',
                    'name' => 'LiqPay',
                    'fields' => $fields['liqpay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'kyivstar' => [
                    'code' => 'KievStarUAH',
                    'name' => 'Київстар. Мобільні гроші (Україна)',
                    'commission' => [
                        'percent' => 5.5,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['kyivstar'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'privat24' => [
                    'code' => 'Privat24UAH',
                    'name' => 'Інтернет-банк «Приват24»',
                    'commission' => [
                        'percent' => 2,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['privat24'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'bank-transfer' => [
                    'code' => 'BankTransferUAH',
                    'name' => 'Банківський переказ у гривнях',
                    'fields' => $fields['bank-transfer'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'visa' => [
                    'code' => 'CreditCardUAH',
                    'name' => 'VISA',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['visa'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'mastercard' => [
                    'code' => 'CreditCardUAH',
                    'name' => 'MasterCard',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['mastercard'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'KZT' => [
                'walletone' => [
                    'code' => 'WalletOneKZT',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'bank-transfer' => [
                    'code' => 'BankTransferKZT',
                    'name' => 'Банківський переказ у тенге',
                    'fields' => $fields['bank-transfer'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'BYN' => [
                'walletone' => [
                    'code' => 'WalletOneBYR',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'webmoney' => [
                    'code' => 'WebMoneyBYR',
                    'name' => 'WebMoney',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['webmoney'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'easypay' => [
                    'code' => 'EasyPayBYR',
                    'name' => 'EasyPay',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['easypay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'erip' => [
                    'code' => 'EripBYR',
                    'name' => 'Единое Расчетное Информационное Пространство (ЕРИП)',
                    'fields' => $fields['erip'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'visa' => [
                    'code' => 'CreditCardBYR',
                    'name' => 'VISA',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['visa'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'mastercard' => [
                    'code' => 'CreditCardBYR',
                    'name' => 'MasterCard',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 3,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['mastercard'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
//            'BYR' => [
//                'walletone' => [
//                    'code' => 'WalletOneBYR',
//                    'name' => 'WalletOne',
//                    'commission' => [
//                        'percent' => 0,
//                        'fix' => 0,
//                        'min' => 0,
//                        'max' => 0,
//                        'value' => 'percent',
//                    ],
//                    'fields' => $fields['walletone'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => false
//                ],
//                'webmoney' => [
//                    'code' => 'WebMoneyBYR',
//                    'name' => 'WebMoney',
//                    'fields' => $fields['webmoney'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => false
//                ],
//                'easypay' => [
//                    'code' => 'EasyPayBYR',
//                    'name' => 'EasyPay',
//                    'fields' => $fields['easypay'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => false
//                ],
//                'erip' => [
//                    'code' => 'EripBYR',
//                    'name' => 'Единое Расчетное Информационное Пространство (ЕРИП)',
//                    'fields' => $fields['erip'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => false
//                ],
//                'visa' => [
//                    'code' => 'CreditCardBYR',
//                    'name' => 'VISA',
//                    'fields' => $fields['visa'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => false
//                ],
//                'mastercard' => [
//                    'code' => 'CreditCardBYR',
//                    'name' => 'MasterCard',
//                    'fields' => $fields['mastercard'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => false
//                ],
//            ],
            'TJS' => [
                'walletone' => [
                    'code' => 'WalletOneTJS',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'AZN' => [
                'walletone' => [
                    'code' => 'WalletOneAZN',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'PLN' => [
                'walletone' => [
                    'code' => 'WalletOnePLN',
                    'name' => 'WalletOne',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['walletone'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'bank-transfer' => [
                    'code' => 'BankTransferPLN',
                    'name' => 'Банковский перевод в польских злотах',
                    'fields' => $fields['bank-transfer'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'GEL' => [
                'bank-transfer' => [
                    'code' => 'BankTransferGEL',
                    'name' => 'Банковский перевод в лари',
                    'fields' => $fields['bank-transfer'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'smartivi' => [
                    'code' => 'SmartiviGEL',
                    'name' => 'Карты Smartivi',
                    'fields' => $fields['smartivi'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
            'MDL' => [
                'bank-transfer' => [
                    'code' => 'BankTransferMDL',
                    'name' => 'Банковский перевод в леях',
                    'fields' => $fields['bank-transfer'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
                'bpay' => [
                    'code' => 'BPayMDL',
                    'name' => 'B-Pay MDL',
                    'commission' => [
                        'percent' => 0,
                        'fix' => 0,
                        'min' => 0,
                        'max' => 0,
                        'value' => 'percent',
                    ],
                    'fields' => $fields['bpay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false
                ],
            ],
        ];
    }
}