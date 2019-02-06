<?php

namespace pps\dengionline;

use api\classes\ApiError;
use Yii;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;
use \pps\payment\Payment;

/**
 * Class DengioOnline
 * @package pps\dengionline
 */
class DengioOnline extends Payment
{
    const API_URL = 'https://gsg.dengionline.com/api';
    const SCI_URL = 'https://paymentgateway.ru/pgw/';
    const STATUS_URL = 'https://www.onlinedengi.ru/api/dol/payment/get/';

    const COMMISSION_UNKNOWN = 'unknown';
    const COMMISSION_BUYER = 'client';
    const COMMISSION_MERCHANT = 'partner';

    /**
     * @var object
     */
    private $_transaction;
    /**
     * @var array
     */
    private $_PS;
    /**
     * @var object
     */
    private $_currencies;
    /**
     * @var object
     */
    private $_requests;
    /**
     * @var int
     */
    private $_project_id;
    /**
     * @var string
     */
    private $_secret_key;
    /**
     * @var int
     */
    private $_mode_type;
    /**
     * work with XML
     */
    // raw xml
    private $rawXML;
    // array returned by the xml parser
    private $valueArray = [];
    private $keyArray = [];

    // arrays for dealing with duplicate keys
    private $duplicateKeys = [];

    // return data
    private $output = [];
    private $status;
    /** @var int */
    private $_query_errno;

    /**
     * Filling the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['project_id'])) {
            throw new InvalidParamException('project_id empty');
        }
        if (empty($data['secret_key'])) {
            throw new InvalidParamException('secret_key empty');
//        elseif (empty($data['mode_type'])) :
//            throw new InvalidParamException('mode_type is not set' . ' - ' . json_encode($data));
//        elseif (intval($data['mode_type']) > 0) :
//            throw new InvalidParamException('mode_type must be greater than 0');
        }

        $this->_project_id = $data['project_id'];
        $this->_secret_key = $data['secret_key'];
//        $this->_mode_type = $data['mode_type'];

        $this->_currencies = static::getSupportedCurrencies();
        $this->_PS = $this->getSupportedCurrenciesReal();
    }

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        //Основной протокол позволяет Проекту принимать от Плательщиков платежи за предоставляемые товары и услуги.
        $validate = $this->validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        if (!empty($params['commission_payer'])) {
//            $commission_payer = $params['commission_payer'] ?? self::COMMISSION_BUYER;
//            if ($commission_payer != self::COMMISSION_BUYER) :
//                $commission_payer = self::COMMISSION_UNKNOWN;
//            endif;
//
//            if ($commission_payer == self::COMMISSION_UNKNOWN) :
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
//            endif;
        }
//
//        $commission_payer = self::COMMISSION_UNKNOWN;
//        if (!empty($this->_PS[$params['currency']][$params['payment_method']])) :
//            switch ($this->_PS[$params['currency']][$params['payment_method']]['commission']['payer']):
//                case static::COMMISSION_BUYER:
//                    $commission_payer = self::COMMISSION_BUYER;
//                    break;
//                case static::COMMISSION_MERCHANT:
//                    $commission_payer = self::COMMISSION_MERCHANT;
//                    break;
//            endswitch;
//        endif;

        return [
            'data' => [
                'fields' => static::getFields($params['currency'], $params['payment_method'], self::WAY_DEPOSIT),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_refund' => null,
                'commission' => static::getCommission($params['currency'], $params['payment_method'], $params['amount']),
                'buyer_write_off' => null,
//                'commission_payer' => $commission_payer,
            ]
        ];
    }

    /**
     * Invoicing for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        //Основной протокол позволяет Проекту принимать от Плательщиков платежи за предоставляемые товары и услуги.
        $this->_transaction = $params['transaction'];
        $this->_requests = $params['requests'];

        $validate = $this->validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, self::WAY_DEPOSIT);

        if (!$validate) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        try {
            $this->_transaction->commission = static::getCommission($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount);
            $this->_transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

//        $commission_payer = self::COMMISSION_UNKNOWN;
//        if (!empty($this->_PS[$this->_transaction->currency][$this->_transaction->payment_method])) :
//            switch ($this->_transaction->commission_payer):
//                case static::COMMISSION_BUYER:
//                    $commission_payer = self::COMMISSION_BUYER;
//                    break;
//                case static::COMMISSION_MERCHANT:
//                    $commission_payer = self::COMMISSION_MERCHANT;
//                    break;
//            endswitch;
//        endif;

        if (!empty($params['commission_payer'])) {
//            $commission_payer = $this->_transaction->commission_payer;
//            if ($commission_payer != self::COMMISSION_BUYER) :
//                $commission_payer = self::COMMISSION_UNKNOWN;
//            endif;
//
//            if ($commission_payer == self::COMMISSION_UNKNOWN) :
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
//            endif;
        }

//        $is_user_pay_comm = ($this->_transaction->commission_payer == self::COMMISSION_BUYER);
        $invoice_amount = $this->_transaction->amount;
//        if ($is_user_pay_comm):
//            $invoice_amount += $this->_transaction->commission;
//        endif;

        $merchant = json_decode($this->_requests['merchant'], true);

        $this->_requests['m_out']->transaction_id = $this->_transaction->id;
        $this->_requests['m_out']->data = $this->_requests['merchant'];
        $this->_requests['m_out']->type = 1;
        $this->_requests['m_out']->save(false);

        $redirectParams = [
            // Идентификатор Проекта, полученный в процессе технического подключения.
            'project' => $this->_project_id,
            // Код метода оплаты и валюты платежа
            'mode_type' => static::getModeTypes($this->_transaction->currency, $this->_transaction->payment_method),
            // Сумма счёта в валюте, указанной параметром paymentCurrency (см.ниже)
            'amount' => $invoice_amount,
            // Валюта счёта. Возможные значения: USD, RUB, EUR, UAH
            'paymentCurrency' => $this->_transaction->currency,
            // Идентификатор пользователя или заказа. В большинстве Платёжных систем используется
            //в качестве описания/назначения платежа. Если в Проекте не используются идентификаторы
            //пользователей или заказов, этот параметр может содержать любое значение
            'nickname' => 'PPS deposit',
            // Идентификатор платежа в учётной системе Проекта
            'order_id' => $this->_transaction->id,

            // Не ообовязкові поля

            // Текстовый комментарий к платежу. Этот параметр замещает nickname при заполнении назначения/описания платежа
            'comment' => 'deposit №' . $this->_transaction->id,
            // Дополнительные сведения, необходимые для совершения платежа или сбора статистики на стороне Проекта
            'nick_extra' => 'details of payment',
            // Url возврата пользователя на сайт проекта в случае успешно выставленного/оплаченного счета
            'return_url_success' => $params['success_url'],
            // Url возврата пользователя на сайт проекта в случае неуспешно выставленного/оплаченного счета
            'return_url_fail' => $params['fail_url'],
        ];

        if (!empty($merchant['phone'])) {
            $redirectParams['qiwi_phone'] = $merchant['phone'];
            $redirectParams['sendQIWIPayment'] = 1;
        }

        if (!empty($merchant['userid'])) {
            $redirectParams['AlfaClickUserID'] = $merchant['userid'];
            $redirectParams['xml'] = 1;
        }

        if (in_array($this->_mode_type, [54, 62])) {
            $redirectParams['xml'] = 1;
        }

        $this->_transaction->query_data = json_encode($redirectParams);

        $this->_requests['out']->transaction_id = $this->_transaction->id;
        $this->_requests['out']->data = $this->_transaction->query_data;
        $this->_requests['out']->type = 2;
        $this->_requests['out']->save(false);

        Yii::info(['query' => $redirectParams, 'result' => []], 'payment-dengionline-invoice');

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
            'data' => $this->_transaction::getDepositAnswer($this->_transaction)
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
        $receiveData = $data['receive_data'];

        if (in_array($this->_transaction->status, self::getFinalStatuses())) {
            // check if transaction was executed
            return true;
        }
        if (!$this->checkReceivedSign($receiveData)) {
            return false;
        }
        if (floatval($this->_transaction->amount) != floatval($receiveData['amount_transfer'])) {
            // If amounts not equal
            $this->logAndDie(
                'NixMoney receive() transaction amount not equal received amount',
                'Transaction amount = ' . $this->_transaction->amount . "\nreceived amount = " . $receiveData['amount_transfer'],
                "Transaction amount not equal received amount"
            );
        }
        if ($data['currency'] != $receiveData['currency_transfer']) {
            // If different currency
            $this->logAndDie(
                'NixMoney receive() different currency',
                'Casino currency = ' . $data['currency'] . "\nreceived currency = " . $receiveData['currency_transfer'],
                "Different currency"
            );
        }

        $this->_transaction->status = self::STATUS_SUCCESS;
        if (intval($receiveData['paymentid']) > 0) {
            $this->_transaction->external_id = $receiveData['paymentid'];
        }
        $this->_transaction->save(false);

        return true;
    }

    /**
     * Check if the seller has enough money.
     * Getting required fields for withdraw.
     * This method should be called before withDraw()
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params)
    {
        // Протокол Global Service Gateway (GSG) — порядок взаимодействия с процессинговой системой,
        // позволяющей совершать платежи (в том числе с помощью API) в пользу широкого спектра поставщиков из различных стран.

        $validate = $this->validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate . ' ddd',
                'code' => self::$error_code
            ];
        }

        $commission_payer = self::COMMISSION_UNKNOWN;
        if (!empty($this->_PS[$params['currency']][$params['payment_method']])) {
            if (!empty($this->_PS[$params['currency']][$params['payment_method']]['commission'])) {
                switch ($this->_PS[$params['currency']][$params['payment_method']]['commission']['payer']) {
                    case static::COMMISSION_BUYER:
                        $commission_payer = self::COMMISSION_BUYER;
                        break;
                    case static::COMMISSION_MERCHANT:
                        $commission_payer = self::COMMISSION_MERCHANT;
                        break;
                }
            }
        }

        $query_balance = [
            'action' => 'balance',                     //* Идентификатор желаемого действия
            'timestamp' => round(microtime(true) * 1000), //* Дата и время выполнения запроса (Unix time)
        ];

        $result_balance = $this->queryWithDraw($query_balance);

        \Yii::info(
            [
                'params' => $params,
                'query' => $query_balance,
                'result' => $result_balance
            ],
            'payment-dengionline-prewithdraw_balance'
        );

        $balance = $this->verifyBalance($result_balance, $params['currency'], $params['amount']);

        if ($balance['success']) {
            $answer = [
                'data' => [
                    'fields' => $this->_PS[$params['currency']][$params['payment_method']]['fields'][self::WAY_WITHDRAW] ?? [],
                    'currency' => $params['currency'],
                    'amount' => $params['amount'],
                    'buyer_receive' => null,
                    'merchant_write_off' => null,
                    'commission' => static::getCommission($params['currency'], $params['payment_method'], $params['amount']),
                    'commission_payer' => $commission_payer,
                ],
            ];
        } else {
            $answer = [
                'status' => 'error',
                'message' => $balance['message'],
                'code' => ApiError::LOW_BALANCE
            ];
        }

        return $answer;
    }

    /**
     * Check balance
     * @param array $data
     * @return array
     */
    private function verifyBalance(array $data, string $currency, float $amount): array
    {
        $return = [
            'status' => 'error',
            'success' => false,
            'err_code' => 1,
            'message' => "An error occurred while requesting a balance",
        ];

        if ((isset($data['response'])) && (isset($data['response']['status']))) {
            if ($data['response']['status'] == 1) {
                if (strpos(static::getCurrency($data['response']['currency']), $currency) !== false) {
                    if (floatval($data['response']['balance']) >= floatval($amount)) {
                        $return = [
                            'status' => 'successful',
                            'success' => true,
                            'err_code' => 0,
                            'message' => "",
                        ];
                    } else {
                        $return['err_code'] = 4;
                        $return['message'] = "There is not enough money on the balance:\n " .
                            'Balance = ' . $data['response']['balance'] . " " . static::getCurrency($data['response']['currency']) . "\n " .
                            "Payment amount = " . $amount . " " . $currency;
                    }
                } else {
                    $return['err_code'] = 3;
                    $return['message'] = "Not matching wallet currency and payment:\n " .
                        'Wallet currency - ' . $data['response']['currency'] . "\n " .
                        "Payment currency - " . $currency;
                }
            } else {
                $return['err_code'] = 2;
                $statusData = static::getTranscriptStatus(self::WAY_WITHDRAW);
                $return['message'] = "Not successful response status:\n " .
                    'Response status code = ' . $data['response']['status'] . "\n " .
                    "Response status text - " . $statusData[$data['response']['status']]['text'];
            }
        }

        return $return;
    }

    /**
     * Check pay
     * @param array $data
     * @return int
     */
    private static function verifyCheck(array $data): int
    {
        if ((isset($data['response'])) && (isset($data['response']['status']))) {
            if ($data['response']['status'] == 1) {
                return $data['response']['invoice'];
            }
        }

        return 0;
    }

    /**
     * @param array $data
     * @return bool
     */
    private static function verifyPay(array $data): bool
    {
        return ($data['response']['status'] == 1) ?? false;
    }

    /**
     * @param array $data
     * @return bool
     */
    private static function verifyStatus(array $data): bool
    {
        if ($data['response']['status'] == 1) {
            return ($data['response']['pay_status'] == 'paid');
        }

        return false;
    }

    /**
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     */
    public function withDraw(array $params)
    {
        // Протокол Global Service Gateway (GSG) — порядок взаимодействия с процессинговой системой,
        // позволяющей совершать платежи (в том числе с помощью API) в пользу широкого спектра поставщиков из различных стран.
        $this->_transaction = $params['transaction'];
        $this->_requests = $params['requests'];
        $merch_params = json_decode($this->_requests['merchant'], true);

        $query_data = [
            'balance' => [],
            'check' => [],
            'pay' => [],
            'status' => [],
        ];
        $requests_data = [
            'balance' => [
                'out' => clone $params['requests']['out'],
                'pa_in' => clone $params['requests']['pa_in'],
            ],
            'check' => [
                'out' => clone $params['requests']['out'],
                'pa_in' => clone $params['requests']['pa_in'],
            ],
            'pay' => [
                'out' => clone $params['requests']['out'],
                'pa_in' => clone $params['requests']['pa_in'],
            ],
            'status' => [
                'out' => clone $params['requests']['out'],
                'pa_in' => clone $params['requests']['pa_in'],
            ],
        ];
        $result_data = [
            'balance' => [],
            'check' => [],
            'pay' => [],
            'status' => [],
        ];

        $validate = $this->validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, self::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        try {
            $this->_transaction->commission = static::getCommission($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount);
            $this->_transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $commission_payer = self::COMMISSION_UNKNOWN;
        switch ($this->_transaction->commission_payer) {
            case static::COMMISSION_BUYER:
                $commission_payer = self::COMMISSION_BUYER;
                break;
            case static::COMMISSION_MERCHANT:
                $commission_payer = self::COMMISSION_MERCHANT;
                break;
        }

        $this->_requests['m_out']->transaction_id = $this->_transaction->id;
        $this->_requests['m_out']->data = $this->_requests['merchant'];
        $this->_requests['m_out']->type = 1;
        $this->_requests['m_out']->save(false);

        $query_balance = [
            'action' => 'balance',                     //* Идентификатор желаемого действия
            'timestamp' => round(microtime(true) * 1000), //* Дата и время выполнения запроса (Unix time)
        ];

        $query_data['balance'] = json_encode($query_balance);

        $requests_data['balance']['out']->transaction_id = $this->_transaction->id;
        $requests_data['balance']['out']->data = $query_data['balance'];
        $requests_data['balance']['out']->type = 2;
        $requests_data['balance']['out']->save(false);

        $result_balance = $this->queryWithDraw($query_balance);

        $result_data['balance'] = json_encode($result_balance);

        $requests_data['balance']['pa_in']->transaction_id = $this->_transaction->id;
        $requests_data['balance']['pa_in']->data = $result_data['balance'];
        $requests_data['balance']['pa_in']->type = 3;
        $requests_data['balance']['pa_in']->save(false);

        \Yii::info(
            [
                'query' => $query_balance,
                'result' => $result_balance,
                'params' => $params,
                'merch_params' => $merch_params,
            ],
            'payment-dengionline-withdraw_balance'
        );

        $answer = $this->verifyBalance($result_balance, $merch_params['currency'], $merch_params['amount']);

        if ($answer['success']) {
            $is_merch_pay_comm = ($this->_transaction->commission_payer == self::COMMISSION_BUYER);
            $invoice_amount = $this->_transaction->amount;

            if ($is_merch_pay_comm) {
                $invoice_amount += $this->_transaction->commission;
            }

            $query_check = [
                //* Идентификатор желаемого действия
                'action' => 'check',
                //* Дата и время выполнения запроса (Unix time)
                'timestamp' => round(microtime(true) * 1000),
                //Дополнительные параметры запроса к системе.
                //Возможные вложенные элементы тега params приведены в описании каждого действия
                'params' => [
                    //* ID транзакции во внешней системе (системе Партнера)
                    'txn_id' => $this->_transaction->id,
                    //* ID услуги
                    'paysystem' => $this->_PS[$this->_transaction->currency][$this->_transaction->payment_method]['code'],
                    //* ID аккаунта в системе получателя платежа (сервис-провайдера). Например, номер карты.
                    //Для проведения платежей в некоторые услуги требуется указывать дополнительные параметры.
                    //Список параметров и требования к ним приведены в теге params ответа на запрос commissions.
                    'account' => $merch_params['account'],
                    //Сумма выплаты. Если сумма не указана то будут выведены курсы пересчёта из валюты запроса в
                    //валюту услуги или её необходимо передать в числе параметров действия pay.
                    //Если сумма указана – будет выведена информация о конвертации.
                    'amount' => $invoice_amount,
                    //ID валюты платежа. 
                    //Если валюта не указана, то считается, что сумма платежа указана в валюте основного баланса проекта.
                    //Внимание!
                    //Валюта платежа не может быть изменена!
                    //Если в check-запросе указана одна валюта, а в pay-запросе - другая, будет возвращена ошибка 21 - BAD_CURRENCY
                    'currency' => static::getCurrencyId($this->_transaction->currency),
                ],
            ];
            $query_data['check'] = json_encode($query_check);

            $this->_transaction->query_data = json_encode($query_data);

            $requests_data['check']['out']->transaction_id = $this->_transaction->id;
            $requests_data['check']['out']->data = $query_data['check'];
            $requests_data['check']['out']->type = 12;
            $requests_data['check']['out']->save(false);

            $result_check = $this->queryWithDraw($query_check);

            $result_data['check'] = json_encode($result_check);
            $this->_transaction->result_data = json_encode($result_data);

            $requests_data['check']['pa_in']->transaction_id = $this->_transaction->id;
            $requests_data['check']['pa_in']->data = $result_data['check'];
            $requests_data['check']['pa_in']->type = 13;
            $requests_data['check']['pa_in']->save(false);

            \Yii::info(
                [
                    'query' => $query_check,
                    'result' => $result_check
                ],
                'payment-dengionline-withdraw_check'
            );

            $invoice = static::verifyCheck($result_check);
            if ($invoice > 0) {
                $this->_transaction->external_id = $invoice;
                $this->_transaction->save(false);

                //PAY
                $query_pay = [
                    //* Идентификатор желаемого действия
                    'action' => 'pay',
                    //* Дата и время выполнения запроса (Unix time)
                    'timestamp' => round(microtime(true) * 1000),
                    //Дополнительные параметры запроса к системе.
                    //Возможные вложенные элементы тега params приведены в описании каждого действия
                    'params' => [
                        //ID транзакции, полученный в действии check
                        'invoice' => $invoice,
                        //* ID транзакции во внешней системе (системе партнера)
                        'txn_id' => $this->_transaction->id,
                        //Сумма выплаты
                        //Параметр обязателен, если сумма не была указана в действии check.
                        //Если сумма была указана в действии check, то в текущем действии значение игнорируется.
                        'amount' => $invoice_amount,
                        //ID валюты платежа. 
                        //Если валюта не была указана в check-запросе, то считается, что сумма платежа указана в валюте основного баланса проекта.
                        //Если валюта была указана в действии check, то в текущем действии значение игнорируется.
                        //Валюта платежа не может быть изменена!
                        //Если в check-запросе указана одна валюта, а в pay-запросе - другая, будет возвращена ошибка 21 - BAD_CURRENCY
                        'currency' => static::getCurrencyId($this->_transaction->currency),
                        //Комментарий, который получатель выплаты увидит в своем кошельке (только для Webmoney и QIWI)
                        'comment' => 'Pay withdraw ' . $invoice,
                    ],
                ];

                $query_data['pay'] = json_encode($query_pay);
                $this->_transaction->query_data = json_encode($query_data);

                $requests_data['pay']['out']->transaction_id = $this->_transaction->id;
                $requests_data['pay']['out']->data = $query_data['pay'];
                $requests_data['pay']['out']->type = 22;
                $requests_data['pay']['out']->save(false);

                $result_pay = $this->queryWithDraw($query_pay);

                $result_data['pay'] = json_encode($result_pay);
                $this->_transaction->result_data = json_encode($result_data);

                $requests_data['pay']['pa_in']->transaction_id = $this->_transaction->id;
                $requests_data['pay']['pa_in']->data = $result_data['pay'];
                $requests_data['pay']['pa_in']->type = 23;
                $requests_data['pay']['pa_in']->save(false);

                $this->_transaction->save(false);

                \Yii::info(
                    [
                        'query' => $query_pay,
                        'result' => $result_pay,
                    ],
                    'payment-dengionline-withdraw_pay'
                );

                //PAY_STATUS
                if ($this->verifyPay($result_pay)) {
                    $query_status = [
                        //* Идентификатор желаемого действия
                        'action' => 'pay_status',
                        //* Дата и время выполнения запроса (Unix time)
                        'timestamp' => round(microtime(true) * 1000),
                        //Дополнительные параметры запроса к системе.
                        //Возможные вложенные элементы тега params приведены в описании каждого действия
                        'params' => [
                            //ID транзакции, полученный в действии check
                            'invoice' => $invoice,
                            //* ID транзакции во внешней системе (системе партнера)
                            'txn_id' => $this->_transaction->id,
                        ],
                    ];

                    $query_data['status'] = json_encode($query_status);
                    $this->_transaction->query_data = json_encode($query_data);

                    $requests_data['status']['out']->transaction_id = $this->_transaction->id;
                    $requests_data['status']['out']->data = $query_data['status'];
                    $requests_data['status']['out']->type = 32;
                    $requests_data['status']['out']->save(false);

                    $result_status = $this->queryWithDraw($query_status);

                    $result_data['status'] = json_encode($result_status);
                    $this->_transaction->result_data = json_encode($result_data);

                    $requests_data['status']['pa_in']->transaction_id = $this->_transaction->id;
                    $requests_data['status']['pa_in']->data = $result_data['status'];
                    $requests_data['status']['pa_in']->type = 33;
                    $requests_data['status']['pa_in']->save(false);

                    $this->_transaction->save(false);

                    \Yii::info(
                        [
                            'query' => $query_status,
                            'result' => $result_status,
                            '_transaction' => $this->_transaction,
                        ],
                        'payment-dengionline-withdraw_status'
                    );

                    if ($this->verifyStatus($result_status)) {
                        $this->_transaction->receive = $result_status['response']['outcome'];
                        $this->_transaction->status = self::STATUS_SUCCESS;
                        $this->_transaction->save(false);
                    }
                }

                $answer = [
                    'data' => [
                        'id' => $this->_transaction->id,
                        'transaction_id' => $this->_transaction->merchant_transaction_id,
                        'status' => $this->_transaction->status,
                        'buyer_receive' => $this->_transaction->receive,
                        'amount' => $invoice_amount,
                        'currency' => $this->_transaction->currency,
                        'commission' => $this->_transaction->commission,
                        'commission_payer' => $commission_payer,
                    ]
                ];
            }
        } elseif ($this->_query_errno == CURLE_OPERATION_TIMEOUTED) {
            $this->_transaction->status = self::STATUS_TIMEOUT;
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        } elseif ($this->_query_errno != CURLE_OK) {
            $this->_transaction->status = self::STATUS_NETWORK_ERROR;
            $this->_transaction->result_data = $this->_query_errno;
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        }

        $this->_transaction->query_data = json_encode($query_data);
        $this->_transaction->result_data = json_encode($result_data);
        $this->_transaction->save(false);

        return $answer;
    }

    /**
     * Updating not final statuses for deposit
     * @param object $transaction
     * @param null|object $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        $this->_transaction = $transaction;

        if (in_array($transaction->status, self::getNotFinalStatuses())) {
            if ($this->_transaction->way == self::WAY_DEPOSIT) {
                $data = [
                    'payment' => $transaction->external_id, // ?????????????
                    'order' => $transaction->id,
                ];

                $response = $this->queryInvoice(static::STATUS_URL, $data);
            } else {
                $data = [
                    //* Идентификатор желаемого действия
                    'action' => 'pay_status',
                    //* Дата и время выполнения запроса (Unix time)
                    'timestamp' => round(microtime(true) * 1000),
                    //Дополнительные параметры запроса к системе.
                    //Возможные вложенные элементы тега params приведены в описании каждого действия
                    'params' => [
                        //ID транзакции, полученный в действии check
                        'invoice' => $transaction->external_id,
                        //* ID транзакции во внешней системе (системе партнера)
                        'txn_id' => $this->_transaction->id,
                    ],
                ];

                $response = $this->queryWithDraw($data);
            }

            if (isset($model_req)) {
                // Saving the data that came from the PA in the unchanged state
                $model_req->transaction_id = $this->_transaction->id;
                $model_req->data = json_encode($response);
                $model_req->type = 8;
                $model_req->save(false);
            }

            if ($this->_transaction->way == self::WAY_DEPOSIT) {
                Yii::info($response, 'status-dengionline-deposite');

                if (isset($response['status'])) {
                    if ($this->isFinal($response['status'])) {
                        if ($this->isSuccessState($response['status'])) {
                            $this->_transaction->status = self::STATUS_SUCCESS;
                        } else {
                            switch ($response['status']) {
                                case 5:
                                case 7:
                                    $this->_transaction->status = self::STATUS_ERROR;
                                    break;
                                case 14:
                                    $this->_transaction->status = self::STATUS_CANCEL;
                                    break;
                            }
                        }
                    } else {
                        $this->_transaction->status = self::STATUS_PENDING;
                    }

                    $this->_transaction->save(false);

                    return true;
                }
            } else {
                Yii::info($response, 'status-dengionline-pay_status');

                if ($response['response']['status'] == 1) {
                    if ($this->isFinal($response['pay_status'], 'pay_status')) {
                        if ($this->isSuccessState($response['pay_status'], 'pay_status')) {
                            $this->_transaction->status = self::STATUS_SUCCESS;
                        } else {
                            switch ($response['pay_status']) {
                                case 'error':
                                    $this->_transaction->status = self::STATUS_ERROR;
                                    break;
                                case 'canceled':
                                    $this->_transaction->status = self::STATUS_CANCEL;
                                    break;
                            }
                        }
                    } else {
                        $this->_transaction->status = self::STATUS_PENDING;
                    }

                    $this->_transaction->save(false);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $status
     * @param string $message
     * @return string
     */
    private function sendResponse($status = 'YES', $message = '')
    {
        return '<?xml version="1.0" encoding="UTF-8"?><result><code>' . $status . '</code><comment>' . $message . '</comment></result>';
    }

    /**
     * @param string $url
     * @param array $params
     * @return array|mixed
     */
    private function queryInvoice(string $url, array $params = [])
    {
        $post_data = json_encode($params);

        $headers = [
            'X-DOL-Project: ' . $this->_project_id,
            'X-DOL-Sign: ' . hash_hmac('sha1', $post_data, $this->_secret_key),
        ];

        $sh = curl_init();
        curl_setopt($sh, CURLOPT_URL, $url);
        curl_setopt($sh, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($sh, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($sh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($sh, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($sh, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($sh, CURLOPT_POST, true);
        curl_setopt($sh, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($sh, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($sh);
        $curl_errno = curl_errno($sh);
        $this->_query_errno = $curl_errno;
        $curl_error = curl_error($sh);
        $http_code = curl_getinfo($sh, CURLINFO_HTTP_CODE);
        curl_close($sh);

        if ($curl_errno > 0) {
            return [
                'status' => 'error',
                'message' => 'Error number = ' . $curl_errno . '. Error text = ' . $curl_error
            ];
        } elseif ($http_code != 200) {
            return [
                'status' => 'error',
                'message' => "Payment system send error. HTTP Status #" . $http_code . ". Response: " . print_r($response, true),
            ];
        }

        return json_decode($response, true);
    }

    /**
     * @param array $params
     * @return array|bool
     */
    private function queryWithDraw(array $params = [])
    {
        $check_status = ($params['action'] == 'pay');

        $xml_req = $this->_createXML($params);

        $headers = [
            'X-DOL-Project: ' . $this->_project_id,
            'X-DOL-Sign: ' . hash_hmac('sha1', $xml_req, $this->_secret_key),
        ];

        do {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, static::API_URL . "/" . $this->_project_id . "/");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_req);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $curl_errno = curl_errno($ch);
            $this->_query_errno = $curl_errno;
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curl_errno > 0) {
                return [
                    'status' => 'error',
                    'message' => 'Error number = ' . $curl_errno . '. Error text = ' . $curl_error
                ];
            } elseif ($http_code != 200) {
                return [
                    'status' => 'error',
                    'message' => "Payment system send error. HTTP Status #" . $http_code . ". Response: " . print_r($response, true),
                ];
            }

            $xml_resp = new \DomDocument('1.0');
            $xml_resp->loadXML(str_replace('> <', '><', $response));

            $xml_resp->preserveWhiteSpace = false;
            $xml_resp->formatOutput = true;

            if (!$this->XMLParser($xml_resp->saveXML())) {
                return false;
            }

            $returns = $this->getOutputParsing();

            #  только для запроса pay
            if ($check_status) {
                #  если запрос pay завершился со статусом 1000,
                #  следует повторить pay запрос с теми же параметрами.
                if (!isset($returns['response'])) {
                    $check_status = false;
                } elseif (!isset($returns['response']['status'])) {
                    $check_status = false;
                } elseif ($returns['response']['status'] != 1000) {
                    $check_status = false;
                }
            }
        } while ($check_status);

        return $returns;
    }

    /**
     * get data from array for _getSign function
     * @param array $dataSet
     * @param array $return
     * @return array
     */
    private static function getDataFromArray(array $dataSet, & $return = [])
    {
        foreach ($dataSet as $key => $value) {
            if (is_array($value)) {
                array_merge($return, static::getDataFromArray($value, $return));
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * Sign function
     * @param array $dataSet
     * @return string
     */
    private function _getSign(array $dataSet): string
    {
        $data = static::getDataFromArray($dataSet);

        ksort($data, SORT_STRING);

        $sign_line = "secret=" . $this->_secret_key;
        foreach ($data as $key => $value) {
            $sign_line .= "&" . str_replace(' ', '+', $key . "=" . $value);
        }

        return sha1($sign_line);
    }

    /**
     * Getting real supported currencies with commission and additional fields
     * @return array
     */
    public function getSupportedCurrenciesReal(): array
    {
        $query = [
            'action' => 'commissions',                 //* Идентификатор желаемого действия
            'timestamp' => round(microtime(true) * 1000), //* Дата и время выполнения запроса (Unix time)
        ];

        $data = $this->queryWithDraw($query);
//        return [$data];

        $parameters = [];
        if ((!empty($data)) && (is_array($data))) {
            if ((!empty($data['response'])) && (!empty($data['response']['status']))) {
                if ($data['response']['status'] == 1) {
                    foreach ($data['response']['services'] as $serv_key => $service) {
                        $currency_cod = static::getCurrency($service['currency_id']);

                        if (!$service['tag']) {
                            $service['tag'] = ('test-' . $serv_key);
                        }

                        $SupportedCurrencies = [
                            'name' => $service['title'],
                            'min' => $service['min_amount'],
                            'max' => $service['max_amount'],
                            'code' => $service['id'],
                            'currency_code' => $service['currency_id'],
                            'commission' => $service['commission'],
                            self::WAY_DEPOSIT => true,
                            self::WAY_WITHDRAW => true,
                        ];

                        $fields = [
                            'account' => [
                                'label' => $service['account_name'],
                                'regex' => $service['account_regexp'],
                            ],
                        ];

                        if ((!empty($service['params'])) && (is_array($service['params']))) {
                            foreach ($service['params'] as $param) {
                                $fields[$param['name']] = [
                                    'label' => $param['descr'],
                                    'regex' => $param['regexp'],
                                ];
                            }
                        }

                        $SupportedCurrencies['fields'] = [
                            self::WAY_DEPOSIT => $fields,
                            self::WAY_WITHDRAW => $fields,
                        ];

                        if ((!empty($service['protection'])) && (is_array($service['protection']))) {
                            $SupportedCurrencies['protection'] = $service['protection'];
                        }

                        $parameters[$currency_cod][$service['tag']] = $SupportedCurrencies;
                    }
                }
            }
        }

        return $parameters;
    }

    /**
     * Get supported currencies ID
     * @param string $currency
     * @return int
     */
    private static function getCurrencyId(string $currency): int
    {
        $currencies = static::getCurrencyList();

        foreach ($currencies as $key => $value) {
            if (strpos($currency, $value) !== false) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Getting supported currency ID
     * @return int
     */
    private static function getCurrencyISO(string $currency): int
    {
        return static::getCurrencyId($currency);
    }

    /**
     * Get supported currencies description text
     * @param int $currency
     * @return string
     */
    private static function getCurrency(int $currency): string
    {
        $currencies = static::getCurrencyList();

        return $currencies[$currency] ?? null;
    }

    /**
     * Check final status
     * @param string $status
     * @param string $way
     * @return bool
     */
    private static function isFinal(string $status, string $way = self::WAY_DEPOSIT): bool
    {
        $status_lib = static::getTranscriptStatus($way);

        return $status_lib[$status]['final'] ?? false;
    }

    /**
     * Check success status
     * @param string $pay_status
     * @param string $way
     * @return bool
     */
    private function isSuccessState(string $pay_status, string $way = self::WAY_DEPOSIT): bool
    {
        return ($way == self::WAY_DEPOSIT) ? in_array(intval($pay_status), [9, 24]) : $pay_status == 'paid';
    }

    /**
     * @param array $params
     * @return bool
     */
    private function checkReceivedSign(array $params): bool
    {
        $expectedSign = md5($params['amount'] . $params['userid'] . $params['paymentid'] . $this->_secret_key);

        if ($params['key'] !== $expectedSign) {
            Yii::error("DengiOnline receive() wrong sign is received: expectedSign = {$expectedSign} \nSign = {$params['key']}", 'payment-dengionline');

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
    public function validateTransaction(string $currency, string $method, float $amount, string $way)
    {
        $currencies = $this->_currencies;

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        if (!isset($currencies[$currency][$method]['mode_type'])) {
            $currencies[$currency][$method]['mode_type'] = 0;
        } else {
            if (intval(($currencies[$currency][$method]['mode_type'])) > 0) {
                if ($this->_mode_type != intval(($currencies[$currency][$method]['mode_type']))) {
                    $this->_mode_type = intval($currencies[$currency][$method]['mode_type']);
                }
            }
        }

        if (
            (intval($currencies[$currency][$method]['mode_type']) == 0) &&
            ($this->_mode_type == 0)
        ) {
            return "Mode_type should to be more than 0";
        }

        if (
            (!isset($currencies[$currency][$method][$way])) ||
            (!$currencies[$currency][$method][$way])
        ) {
            return "Currency does not support '{$way}'";
        }

        if (!empty($this->_PS[$currency][$method])) {
            if (
                (floatval($amount) < floatval($this->_PS[$currency][$method]['min'])) ||
                (floatval($amount) > floatval($this->_PS[$currency][$method]['max']))
            ) {
                if (floatval($amount) < floatval($this->_PS[$currency][$method]['min'])) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
                if (floatval($amount) >= floatval($this->_PS[$currency][$method]['max'])) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;
                return "Amount should to be more than '{$this->_PS[$currency][$method]['min']}' and less than '{$this->_PS[$currency][$method]['max']}'";
            }
        }

        return true;
    }

    /**
     * Get a list of available systems - payees and their parameters
     * @return array|bool
     */
    public function getPaySystems()
    {
        $data = [
            'action' => 'paysystems',                  //* Идентификатор желаемого действия
            'timestamp' => round(microtime(true) * 1000), //* Дата и время выполнения запроса (Unix time)
        ];

        return $this->queryWithDraw($data);
    }

    /**
     * Search for values that the mode_type parameter can take depending on the payment system and the currency of payment
     * @param $currency
     * @param $method
     * @return int
     */
    public function getModeTypes($currency, $method)
    {
        return $this->_PS[$currency][$method]['mode_type'] ?? 0;
    }

    /**
     * Get query for search transaction after success or fail payment
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return isset($data['order']) ? ['id' => $data['order']] : [];
    }

    /**
     * Get transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['order'] ?? 0;
    }

    /**
     * Get success answer for stopping send callback data
     * @return string
     */
    public static function getSuccessAnswer()
    {
        return '<?xml version="1.0" encoding="UTF-8"?><result><code>YES</code></result>';
    }

    /**
     * Get response format for success answer
     * @return string
     */
    public static function getResponseFormat($way = self::WAY_DEPOSIT)
    {
        return ($way == self::WAY_DEPOSIT) ? Response::FORMAT_XML : Response::FORMAT_HTML;
    }

    /**
     * Get fields for filling
     * @param string $currency
     * @param string $payment_method
     * @param string $way
     * @return array
     */
    private static function getFields(string $currency, string $payment_method, string $way): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency][$payment_method]['fields'][$way] ?? [];
    }

    /**
     * Get sum of commission
     * @param string $currency
     * @param float $amount
     * @param string $method
     * @param float $amount
     * @return float|string
     */
    private function getCommission(string $currency, string $method, float $amount)
    {
        $returns = 0;

        if (!empty($this->_PS[$currency][$method]['commission'])) {
            $commissions = $this->_PS[$currency][$method]['commission'];

            // Враховуємо фіксовану частину комісії
            if (floatval($commissions['fix']) > 0) {
                $returns = $commissions['fix'];
            }

            // Враховуємо відсоткову частину комісії
            if (floatval($commissions['percent']) > 0) {
                if ($commissions['payer'] == static::COMMISSION_MERCHANT) :
                    $returns += $amount * $commissions['percent'] / (100 - $commissions['percent']);
                elseif ($commissions['payer'] == static::COMMISSION_BUYER) :
                    $returns += $amount * $commissions['percent'] / 100;
                endif;
            }

            // Перевіряємо чи входить в діапазон комісія (якщо діапазон заданий)
            if (floatval($commissions['min']) > 0 && $returns < $commissions['min']) {
                $returns = $commissions['min'];
            }

            if (floatval($commissions['max']) > 0 && $returns > $commissions['max']) {
                $returns = $commissions['max'];
            }

            return round(floatval($returns), 8);
        }

        return 'unknown';
    }

    /**
     * Checks the connection of payment method
     * @return array
     */
    public static function getListOfPaymentMethod(): array
    {
        return [];
    }

    /******************************** XML Function ********************************/

    /**
     * XML creation function for request to PS
     * @param array $dataSet
     * @return string
     */
    private function _createXML(array $dataSet): string
    {
        $requiredFields = ['timestamp', 'action'];

        foreach ($requiredFields as $field) {
            if (!isset($dataSet[$field]) || empty($dataSet[$field])) {
                Yii::error("DengiOnline _createXML() Empty field value $field", 'payment-dengionline');
            }
        }

        $timestamp = intval($dataSet['timestamp']);
        $action = strtolower($dataSet['action']);
        $dataSet['project'] = $this->_project_id;

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<request>
    <project>$this->_project_id</project>
    <timestamp>{$timestamp}</timestamp>
    <action>{$action}</action>";

        if (isset($dataSet['params'])) {
            $params = $dataSet['params'];

            if (is_array($params) && count($params)) {
                $xml .= "<params>";

                foreach ($params as $key => $value) {
                    $xml .= "
            <{$key}>{$value}</{$key}>";
                }

                $xml .= "</params>";
            }
        }

        $xml .= "<sign>" . static::_getSign($dataSet) . "</sign>
</request>";

        return $xml;
    }

    /**
     * XML parcer function
     * @param string $xml
     * @return boolean
     */
    private function XMLParser(string $xml): bool
    {
        $this->rawXML = $xml;
        $parser = xml_parser_create();

        // Parcing XML
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); // Dont mess with my cAsE sEtTings
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);   // Dont bother with empty info

        if (!xml_parse_into_struct($parser, $this->rawXML, $this->valueArray, $this->keyArray)) {
            $this->status = 'error: ' . xml_error_string(xml_get_error_code($parser)) . ' at line ' . xml_get_current_line_number($parser);

            return false;
        }

        xml_parser_free($parser);

        // finding duplicates key in the expanded XML
        for ($i = 0; $i < count($this->valueArray); $i++) {
            // duplicate keys are when two complete tags are side by side
            if ($this->valueArray[$i]['type'] == "complete") {
                if (count($this->valueArray) > ($i + 1)) {
                    if (
                        $this->valueArray[$i + 1]['tag'] == $this->valueArray[$i]['tag'] &&
                        $this->valueArray[$i + 1]['type'] == "complete"
                    ) {
                        $this->duplicateKeys[$this->valueArray[$i]['tag']] = 0;
                    }
                }
            }

            // also when a close tag is before an open tag and the tags are the same
            if ($this->valueArray[$i]['type'] == "close") {
                if (count($this->valueArray) > ($i + 1)) {
                    if (
                        ($this->valueArray[$i + 1]['type'] == "open") &&
                        ($this->valueArray[$i + 1]['tag'] == $this->valueArray[$i]['tag'])
                    ) {
                        $this->duplicateKeys[$this->valueArray[$i]['tag']] = 0;
                    }
                }
            }
        }

        // tmp array used for stacking
        $stack = [];
        $increment = 0;

        foreach ($this->valueArray as $val) {
            if ($val['type'] == "open") {
                //if array key is duplicate then send in increment
                if (array_key_exists($val['tag'], $this->duplicateKeys)) :
                    array_push($stack, $this->duplicateKeys[$val['tag']]);
                    $this->duplicateKeys[$val['tag']]++;
                else :
                    // else send in tag
                    array_push($stack, $val['tag']);
                endif;

                if (!empty($val['attributes'])) :
                    if (is_array($val['attributes'])) :
                        if (!isset($stack['attributes'])) :
                            array_push($stack, 'attributes');
                        endif;

                        foreach ($val['attributes'] as $attribute => $value) :
                            array_push($stack, $attribute);

                            $this->setArrayValue($this->output, $stack, $value);
                            array_pop($stack);
                        endforeach;

                        array_pop($stack);
                    endif;
                endif;
            } elseif ($val['type'] == "close") {
                array_pop($stack);

                // reset the increment if they tag does not exists in the stack
                if (array_key_exists($val['tag'], $stack)) :
                    $this->duplicateKeys[$val['tag']] = 0;
                endif;
            } elseif ($val['type'] == "complete") {
                //if array key is duplicate then send in increment
                if (array_key_exists($val['tag'], $this->duplicateKeys)) {
                    array_push($stack, $this->duplicateKeys[$val['tag']]);
                    $this->duplicateKeys[$val['tag']]++;
                } else {
                    // else send in tag
                    array_push($stack, $val['tag']);
                }

                if (!empty($val['attributes'])) {
                    if (isset($val['value'])) {
                        if (!isset($stack['attr'])) :
                            array_push($stack, 'value');
                        endif;

                        $this->setArrayValue($this->output, $stack, $val['value']);
                        array_pop($stack);
                    }

                    if (is_array($val['attributes'])) {
                        if (!isset($stack['attributes'])) {
                            array_push($stack, 'attributes');
                        }

                        foreach ($val['attributes'] as $attribute => $value) {
                            array_push($stack, $attribute);

                            $this->setArrayValue($this->output, $stack, $value);
                            array_pop($stack);
                        }

                        array_pop($stack);
                    }

                    array_pop($stack);
                } else {
                    if (!isset($val['value'])) {
                        $val['value'] = '';
                    }

                    $this->setArrayValue($this->output, $stack, $val['value']);

                    array_pop($stack);
                }
            }

            $increment++;
        }

        $this->status = 'success: xml was parsed';

        return true;
    }

    /**
     * the function of entering the result into an array
     * @param $array
     * @param $stack
     * @param $value
     * @return mixed
     */
    private function setArrayValue(&$array, $stack, $value)
    {
        if ($stack) {
            $key = array_shift($stack);
            $this->setArrayValue($array[$key], $stack, $value);

            return $array;
        } else {
            $array = $value;
        }
    }

    /**
     * the function of obtaining the result of the parsing XML
     * @return array
     */
    public function getOutputParsing(): array
    {
        return $this->output;
    }

    /**
     * the function of obtaining the status of XML parsing
     * @return string
     */
    public function getStatusParsing(): string
    {
        return $this->status;
    }

    /****************************** Handbook function *****************************/

    /**
     * Getting currencies list
     * @return array
     */
    public static function getCurrenciesList(): array
    {
        $returns = [];
        $currencies = static::getSupportedCurrencies(false);

        foreach ($currencies as $currency => $arrays) {
            foreach ($arrays AS $key => $value) {
                $returns[$key][] = $currency;
            }

            $returns['dengionline'][] = $currency;
        }

        return $returns;
    }

    /**
     * Getting supported currencies
     * @return array
     */
    public static function getSupportedCurrencies(bool $getListCurr = true): array
    {
        $fields = static::getApiFields($getListCurr);
        $names = static::getNames();

        return [
            'RUB' => [
                'card' => [
                    'name' => $names['card'],
                    'mode_type' => 0,
                    'fields' => $fields['card'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => [],
                ],
                'alfaclick' => [
                    'name' => $names['alfaclick'],
                    'mode_type' => 0,
                    'fields' => $fields['alfaclick'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'qiwi-wallet' => [
                    'name' => $names['qiwi-wallet'],
                    'mode_type' => 0,
                    'fields' => $fields['qiwi-wallet'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'webmoney' => [
                    'name' => $names['webmoney'] . ' WMR',
                    'mode_type' => 0,
                    'fields' => $fields['webmoney'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'all',
                ],
                'wpskb' => [
                    'name' => $names['wpskb'],
                    'mode_type' => 204,
                    'fields' => $fields['wpskb'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'yamoney' => [
                    'name' => $names['yamoney'],
                    'mode_type' => 0,
                    'fields' => $fields['yamoney'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'all',
                ],
                'alipay' => [
                    'name' => $names['alipay'],
                    'mode_type' => 0,
                    'fields' => $fields['alipay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'China',
                ],
                'rapida' => [
                    'name' => $names['rapida'],
                    'mode_type' => 54,
                    'fields' => $fields['rapida'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'euroset' => [
                    'name' => $names['euroset'],
                    'mode_type' => 62,
                    'fields' => $fields['euroset'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'svyaznoy' => [
                    'name' => $names['svyaznoy'],
                    'mode_type' => 230,
                    'fields' => $fields['svyaznoy'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'beznal' => [
                    'name' => $names['beznal'],
                    'mode_type' => 351,
                    'fields' => $fields['beznal'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'sms11' => [
                    'name' => $names['sms11'],
                    'mode_type' => 11,
                    'fields' => $fields['sms11'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'SNG',
                ],
                'sms24' => [
                    'name' => $names['sms24'],
                    'mode_type' => 24,
                    'fields' => $fields['sms24'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'SNG',
                ],
                'beeline' => [
                    'name' => $names['beeline'],
                    'mode_type' => 45,
                    'fields' => $fields['beeline'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'mts' => [
                    'name' => $names['mts'],
                    'mode_type' => 0,
                    'fields' => $fields['mts'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'megafon' => [
                    'name' => $names['megafon'],
                    'mode_type' => 0,
                    'fields' => $fields['megafon'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'kassira-net' => [
                    'name' => $names['kassira-net'],
                    'mode_type' => 42,
                    'fields' => $fields['kassira-net'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'qiwi' => [
                    'name' => $names['qiwi'],
                    'mode_type' => 18,
                    'fields' => $fields['qiwi'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'plata-online' => [
                    'name' => $names['plata-online'],
                    'mode_type' => 0,
                    'fields' => $fields['plata-online'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Moldova',
                ],
                'plata-belarus' => [
                    'name' => $names['plata-belarus'],
                    'mode_type' => 641,
                    'fields' => $fields['plata-belarus'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Belarus',
                ],
                'test' => [
                    'name' => $names['test'] . ' WMR',
                    'mode_type' => 1002,
                    'fields' => $fields['test'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                    'region' => 'all',
                ],
                'test-0' => [
                    'name' => $names['test-0'],
                    'mode_type' => 1053734,
                    'fields' => $fields['test-0'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'all',
                ],
                'test-1' => [
                    'name' => $names['test-1'],
                    'mode_type' => 1053737,
                    'fields' => $fields['test-1'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'all',
                ],
                'test-3' => [
                    'name' => $names['test-3'],
                    'mode_type' => 1053740,
                    'fields' => $fields['test-3'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'all',
                ],
            ],
            'USD' => [
                'card' => [
                    'name' => $names['card'],
                    'mode_type' => 0,
                    'fields' => $fields['card'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => [],
                ],
                'qiwi-wallet' => [
                    'name' => $names['qiwi-wallet'],
                    'mode_type' => 0,
                    'fields' => $fields['qiwi-wallet'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'RF',
                ],
                'webmoney' => [
                    'name' => $names['webmoney'] . ' WMZ',
                    'mode_type' => 1,
                    'fields' => $fields['webmoney'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'all',
                ],
                'alipay' => [
                    'name' => $names['alipay'],
                    'mode_type' => 0,
                    'fields' => $fields['alipay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'China',
                ],
                'plata-online' => [
                    'name' => $names['plata-online'],
                    'mode_type' => 0,
                    'fields' => $fields['plata-online'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Moldova',
                ],
                'plata-belarus' => [
                    'name' => $names['plata-belarus'],
                    'mode_type' => 641,
                    'fields' => $fields['plata-belarus'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Belarus',
                ],
                'test' => [
                    'name' => $names['test'] . ' WMZ',
                    'mode_type' => 1001,
                    'fields' => $fields['test'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                    'region' => 'all',
                ],
            ],
            'EUR' => [
                'card' => [
                    'name' => $names['card'],
                    'mode_type' => 0,
                    'fields' => $fields['card'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => [],
                ],
                'webmoney' => [
                    'name' => $names['webmoney'] . ' WME',
                    'mode_type' => 3,
                    'fields' => $fields['webmoney'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'all',
                ],
                'alipay' => [
                    'name' => $names['alipay'],
                    'mode_type' => 0,
                    'fields' => $fields['alipay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'China',
                ],
                'plata-online' => [
                    'name' => $names['plata-online'],
                    'mode_type' => 0,
                    'fields' => $fields['plata-online'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Moldova',
                ],
                'plata-belarus' => [
                    'name' => $names['plata-belarus'],
                    'mode_type' => 641,
                    'fields' => $fields['plata-belarus'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Belarus',
                ],
                'test' => [
                    'name' => $names['test'] . ' WME',
                    'mode_type' => 1003,
                    'fields' => $fields['test'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => false,
                    'region' => 'all',
                ],
            ],
            'UAH' => [
                'card' => [
                    'name' => $names['card'],
                    'mode_type' => 0,
                    'fields' => $fields['card'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => [],
                ],
                'alipay' => [
                    'name' => $names['alipay'],
                    'mode_type' => 0,
                    'fields' => $fields['alipay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'China',
                ],
                'qiwi' => [
                    'name' => $names['qiwi'],
                    'mode_type' => 41,
                    'fields' => $fields['qiwi'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Ukrain',
                ],
                'plata-online' => [
                    'name' => $names['plata-online'],
                    'mode_type' => 0,
                    'fields' => $fields['plata-online'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Moldova',
                ],
                'plata-belarus' => [
                    'name' => $names['plata-belarus'],
                    'mode_type' => 641,
                    'fields' => $fields['plata-belarus'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Belarus',
                ],
            ],
            'KZT' => [
                'card' => [
                    'name' => $names['card'],
                    'mode_type' => 0,
                    'fields' => $fields['card'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => [],
                ],
                'alipay' => [
                    'name' => $names['alipay'],
                    'mode_type' => 0,
                    'fields' => $fields['alipay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'China',
                ],
                'qiwi' => [
                    'name' => $names['qiwi'],
                    'mode_type' => 274,
                    'fields' => $fields['qiwi'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Kazahstan',
                ],
                'plata-online' => [
                    'name' => $names['plata-online'],
                    'mode_type' => 0,
                    'fields' => $fields['plata-online'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Moldova',
                ],
                'plata-belarus' => [
                    'name' => $names['plata-belarus'],
                    'mode_type' => 641,
                    'fields' => $fields['plata-belarus'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Belarus',
                ],
                'astana-plat' => [
                    'name' => $names['astana-plat'],
                    'mode_type' => 278,
                    'fields' => $fields['astana-plat'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Kazahstan',
                ],
            ],
            'TJS' => [
                'card' => [
                    'name' => $names['card'],
                    'mode_type' => 0,
                    'fields' => $fields['card'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => [],
                ],
                'alipay' => [
                    'name' => $names['alipay'],
                    'mode_type' => 0,
                    'fields' => $fields['alipay'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'China',
                ],
                'qiwi' => [
                    'name' => $names['qiwi'],
                    'mode_type' => 386,
                    'fields' => $fields['qiwi'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Tajikistan',
                ],
                'plata-online' => [
                    'name' => $names['plata-online'],
                    'mode_type' => 0,
                    'fields' => $fields['plata-online'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Moldova',
                ],
                'plata-belarus' => [
                    'name' => $names['plata-belarus'],
                    'mode_type' => 641,
                    'fields' => $fields['plata-belarus'],
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                    'region' => 'Belarus',
                ],
            ],
//            'LAT' => [
//                'card' => [
//                    'name' => $names['card'],
//                    'mode_type' => 0,
//                    'fields' => $fields['card'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => true,
//                    'region' => [],
//                ],
//                'alipay' => [
//                    'name' => $names['alipay'],
//                    'mode_type' => 0,
//                    'fields' => $fields['alipay'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => true,
//                    'region' => 'China',
//                ],
//                'qiwi' => [
//                    'name' => $names['qiwi'],
//                    'mode_type' => 248,
//                    'fields' => $fields['qiwi'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => true,
//                    'region' => 'Latviya',
//                ],
//                'plata-online' => [
//                    'name' => $names['plata-online'],
//                    'mode_type' => 0,
//                    'fields' => $fields['plata-online'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => true,
//                    'region' => 'Moldova',
//                ],
//                'plata-belarus' => [
//                    'name' => $names['plata-belarus'],
//                    'mode_type' => 641,
//                    'fields' => $fields['plata-belarus'],
//                    self::WAY_DEPOSIT => true,
//                    self::WAY_WITHDRAW => true,
//                    'region' => 'Belarus',
//                ],
//            ],
        ];
    }

    /**
     * @param bool $getListCurr
     * @return array
     */
    public static function getApiFields(bool $getListCurr = true): array
    {
        $currencyList = ($getListCurr) ? static::getCurrenciesList() : [];

        return [
            'card' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'number' => [
                        'label' => 'Account ID',
                        'regex' => '^\d{12,18}$',
                        'required' => true,
                        'currencies' => $currencyList['card'] ?? [],
                    ],
                    'holder' => [
                        'label' => 'Card holder',
                        'regex' => '^[a-zA-Z.-]{3,25} [a-zA-Z.-]{3,25}$',
                        'required' => true,
                        'currencies' => $currencyList['card'] ?? [],
                    ],
                    'expiry' => [
                        'label' => 'Card date expiry',
                        'regex' => '^(0[1-9]|1[0-2])([0-9]{2})$',
                        'required' => true,
                        'currencies' => $currencyList['card'] ?? [],
                    ],
                    'phone' => [
                        'label' => 'Phone',
                        'regex' => '^(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$',
                        'example' => '79123456789',
                        'required' => true,
                        'currencies' => $currencyList['card'] ?? [],
                    ]
                ]
            ],
            'alfaclick' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
//                    'userid' => [
//                        'label' => 'AlfaClick user ID',
//                        'regex' => '^[0-9]{16}$',
//                        'required' => true,
//                        'currencies' => $currencyList['alfaclick'] ?? [],
//                    ]
                ]
            ],
            'qiwi-wallet' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'phone' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '^(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$',
                        'example' => '79123456789',
                        'required' => true,
                        'currencies' => $currencyList['qiwi-wallet'] ?? [],
                    ]
                ]
            ],
            'webmoney' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'account' => [
                        'label' => 'Account ID',
                        'regex' => '^[rR|eE|zZ][\d]{12}',
                        'required' => true,
                        'currencies' => $currencyList['webmoney'] ?? [],
                    ]
                ]
            ],
            'wpskb' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'yamoney' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'account' => [
                        'label' => 'Account ID',
                        'regex' => '^41001\\d*$',
                        'required' => true,
                        'currencies' => $currencyList['yamoney'] ?? [],
                    ]
                ]
            ],
            'alipay' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'rapida' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'euroset' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'svyaznoy' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'beznal' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'sms11' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'sms24' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'beeline' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
//                    'phone' => [
//                        'label' => 'Beeline phone',
//                        'regex' => '^([0-9]{10}$',
//                        'example' => '9123456789',
//                        'required' => true,
//                        'currencies' => $currencyList['beeline'] ?? [],
//                    ]
                ]
            ],
            'mts' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
//                    'phone' => [
//                        'label' => 'MTS phone',
//                        'regex' => '^([0-9]{10}$',
//                        'example' => '9123456789',
//                        'required' => true,
//                        'currencies' => $currencyList['mts'] ?? [],
//                    ]
                ]
            ],
            'megafon' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
//                    'phone' => [
//                        'label' => 'Megafon phone',
//                        'regex' => '^([0-9]{10}$',
//                        'example' => '9123456789',
//                        'required' => true,
//                        'currencies' => $currencyList['megafon'] ?? [],
//                    ]
                ]
            ],
            'kassira-net' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'qiwi' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
//                    'phone' => [
//                        'label' => 'Qiwi number',
//                        'regex' => '^+(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$',
//                        'example' => '+79123456789',
//                        'required' => true,
//                        'currencies' => $currencyList['qiwi'] ?? [],
//                    ]
                ]
            ],
            'plata-online' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'plata-belarus' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'astana-plat' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'dengionline' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'account' => [
                        'label' => 'Account ID in the payee system',
                        'regex' => '^[a-zA-Z0-9\-_\s]+$',
                        'required' => true,
                        'currencies' => $currencyList['dengionline'] ?? [],
                    ],
                ]
            ],
            'test' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'test-0' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'test-1' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'test-3' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ]
        ];
    }

    /**
     * @return array
     */
    private static function getNames(): array
    {
        return [
            'card' => 'Банковские карты',
            'alfaclick' => 'Интернет-банк "Альфа-Клик"',
            'qiwi-wallet' => 'Qiwi wallet',
            'webmoney' => 'WebMoney',
            'wpskb' => 'Web-кошелек ПСКБ',
            'yamoney' => 'Яндекс.Деньги',
            'alipay' => 'Alipay',
            'rapida' => 'Rapida',
            'euroset' => 'Евросеть',
            'svyaznoy' => 'Связной (касса)',
            'beznal' => 'Безналичная оплата',
            'sms11' => 'SMS (11)',
            'sms24' => 'SMS (24)',
            'beeline' => 'Мобильный платеж Билайн',
            'mts' => 'Мобильный платеж МТС',
            'megafon' => 'Мобильный платеж Мегафон',
            'kassira-net' => 'Кассира.Нет',
            'qiwi' => 'Qiwi',
            'plata-online' => 'Plata Online (Молдова)',
            'plata-belarus' => 'Платежи из Республики Беларусь',
            'astana-plat' => 'Astana-Plat',
            'test' => 'Test WebMoney',
            'test-0' => 'Банковские карты Test',
            'test-1' => 'Qiwi-кошелек Test',
            'test-3' => 'Яндекс Test',
        ];
    }

    /**
     * Get currencies list
     * @return array
     */
    private static function getCurrencyList(): array
    {
        return [
            8 => 'ALL',
            12 => 'DZD',
            32 => 'ARS',
            36 => 'AUD',
            44 => 'BSD',
            48 => 'BHD',
            50 => 'BDT',
            51 => 'AMD',
            52 => 'BBD',
            60 => 'BMD',
            64 => 'BTN',
            68 => 'BOB',
            72 => 'BWP',
            84 => 'BZD',
            90 => 'SBD',
            96 => 'BND',
            104 => 'MMK',
            108 => 'BIF',
            116 => 'KHR',
            124 => 'CAD',
            132 => 'CVE',
            136 => 'KYD',
            144 => 'LKR',
            152 => 'CLP',
            156 => 'CNY',
            170 => 'COP',
            174 => 'KMF',
            188 => 'CRC',
            191 => 'HRK',
            192 => 'CUP',
            203 => 'CZK',
            208 => 'DKK',
            214 => 'DOP',
            222 => 'SVC',
            230 => 'ETB',
            232 => 'ERN',
            238 => 'FKP',
            242 => 'FJD',
            262 => 'DJF',
            270 => 'GMD',
            292 => 'GIP',
            320 => 'GTQ',
            324 => 'GNF',
            328 => 'GYD',
            332 => 'HTG',
            340 => 'HNL',
            344 => 'HKD',
            348 => 'HUF',
            352 => 'ISK',
            356 => 'INR',
            360 => 'IDR',
            364 => 'IRR',
            368 => 'IQD',
            376 => 'ILS',
            388 => 'JMD',
            392 => 'JPY',
            398 => 'KZT',
            400 => 'JOD',
            404 => 'KES',
            408 => 'KPW',
            410 => 'KRW',
            414 => 'KWD',
            417 => 'KGS',
            418 => 'LAK',
            422 => 'LBP',
            426 => 'LSL',
            430 => 'LRD',
            434 => 'LYD',
            446 => 'MOP',
            454 => 'MWK',
            458 => 'MYR',
            462 => 'MVR',
            478 => 'MRO',
            480 => 'MUR',
            484 => 'MXN',
            496 => 'MNT',
            498 => 'MDL',
            504 => 'MAD',
            512 => 'OMR',
            516 => 'NAD',
            524 => 'NPR',
            532 => 'ANG',
            533 => 'AWG',
            548 => 'VUV',
            554 => 'NZD',
            558 => 'NIO',
            566 => 'NGN',
            578 => 'NOK',
            586 => 'PKR',
            590 => 'PAB',
            598 => 'PGK',
            600 => 'PYG',
            604 => 'PEN',
            608 => 'PHP',
            634 => 'QAR',
            643 => 'RUB',
            646 => 'RWF',
            654 => 'SHP',
            678 => 'STD',
            682 => 'SAR',
            690 => 'SCR',
            694 => 'SLL',
            702 => 'SGD',
            704 => 'VND',
            706 => 'SOS',
            710 => 'ZAR',
            728 => 'SSP',
            748 => 'SZL',
            752 => 'SEK',
            756 => 'CHF',
            760 => 'SYP',
            764 => 'THB',
            776 => 'TOP',
            780 => 'TTD',
            784 => 'AED',
            788 => 'TND',
            800 => 'UGX',
            807 => 'MKD',
            818 => 'EGP',
            826 => 'GBP',
            834 => 'TZS',
            840 => 'USD',
            858 => 'UYU',
            860 => 'UZS',
            882 => 'WST',
            886 => 'YER',
            901 => 'TWD',
            931 => 'CUC',
            932 => 'ZWL',
            933 => 'BYN',
            934 => 'TMT',
            936 => 'GHS',
            937 => 'VEF',
            938 => 'SDG',
            940 => 'UYI',
            941 => 'RSD',
            943 => 'MZN',
            944 => 'AZN',
            946 => 'RON',
            947 => 'CHE',
            948 => 'CHW',
            949 => 'TRY',
            950 => 'XAF',
            951 => 'XCD',
            952 => 'XOF',
            953 => 'XPF',
            955 => 'XBA',
            956 => 'XBB',
            957 => 'XBC',
            958 => 'XBD',
            959 => 'XAU',
            960 => 'XDR',
            961 => 'XAG',
            962 => 'XPT',
            963 => 'XTS',
            964 => 'XPD',
            965 => 'XUA',
            967 => 'ZMW',
            968 => 'SRD',
            969 => 'MGA',
            970 => 'COU',
            971 => 'AFN',
            972 => 'TJS',
            973 => 'AOA',
            975 => 'BGN',
            976 => 'CDF',
            977 => 'BAM',
            978 => 'EUR',
            979 => 'MXV',
            980 => 'UAH',
            981 => 'GEL',
            984 => 'BOV',
            985 => 'PLN',
            986 => 'BRL',
            990 => 'CLF',
            994 => 'XSU',
            997 => 'USN',
            999 => 'XXX',
        ];
    }

    /**
     * Get transcript of status id
     * @param string $way
     * @return array
     */
    private static function getTranscriptStatus(string $way = self::WAY_DEPOSIT): array
    {
        $status_lib = [
            self::WAY_WITHDRAW => [
                1 => [
                    'text' => 'Успех',
                    'name' => 'OK',
                    'final' => true,
                ],
                2 => [
                    'text' => 'Операция еще выполняется',
                    'name' => 'IN_PROGRESS',
                    'final' => false,
                ],
                3 => [
                    'text' => 'Операция отложена, будет выполнена позже',
                    'name' => 'POSTPONED',
                    'final' => false,
                ],
                4 => [
                    'text' => 'Операция отложена для ручной проверки',
                    'name' => 'MANUAL_VERIFICATION',
                    'final' => false,
                ],
                6 => [
                    'text' => 'Ошибка проведения платежа',
                    'name' => 'PAYMENT_ERROR',
                    'final' => true,
                ],
                11 => [
                    'text' => 'Неверно сформирован XML запроса',
                    'name' => 'BAD_XML',
                    'final' => true,
                ],
                12 => [
                    'text' => 'Неверный формат запроса, отсутствуют нужные теги',
                    'name' => 'BAD_REQUEST',
                    'final' => true,
                ],
                13 => [
                    'text' => 'Неверные авторизационные данные',
                    'name' => 'AUTH_FAILED',
                    'final' => true,
                ],
                14 => [
                    'text' => 'Неверный ID проекта',
                    'name' => 'NO_PROJECT',
                    'final' => true,
                ],
                15 => [
                    'text' => 'Попытка выполнить запрещенное действие',
                    'name' => 'NOT_ALLOWED',
                    'final' => true,
                ],
                16 => [
                    'text' => 'На балансе проекта недостаточно средств',
                    'name' => 'NOT_ENOUGH_MONEY',
                    'final' => false,
                ],
                17 => [
                    'text' => 'Неверное значение тега action',
                    'name' => 'BAD_ACTION',
                    'final' => true,
                ],
                18 => [
                    'text' => 'Неверный ID системы-получателя',
                    'name' => 'BAD_PAYSYSTEM',
                    'final' => true,
                ],
                19 => [
                    'text' => 'Значение ноды params/account не прошло проверку на валидность',
                    'name' => 'BAD_ACCOUNT',
                    'final' => true,
                ],
                20 => [
                    'text' => 'Значение одной из дополнительных нод в params не прошло проверку на валидность',
                    'name' => 'BAD_PARAM',
                    'final' => true,
                ],
                21 => [
                    'text' => 'Неверный ID валюты',
                    'name' => 'BAD_CURRENCY',
                    'final' => true,
                ],
                22 => [
                    'text' => 'Неверный ID инвойса',
                    'name' => 'BAD_INVOICE',
                    'final' => true,
                ],
                23 => [
                    'text' => 'Ошибка на уровне системы-получателя',
                    'name' => 'PS_ERROR',
                    'final' => false,
                ],
                24 => [
                    'text' => 'Повторная попытка оплаты инвойса',
                    'name' => 'DUPLICATE_PAYMENT',
                    'final' => true,
                ],
                25 => [
                    'text' => 'Транзакция с таким номером уже существует',
                    'name' => 'DUPLICATE_TXN',
                    'final' => true,
                ],
                26 => [
                    'text' => 'Неверная сумма платежа',
                    'name' => 'BAD_AMOUNT',
                    'final' => true,
                ],
                27 => [
                    'text' => 'Сумма платежа слишком мала',
                    'name' => 'AMOUNT_TOO_SMALL',
                    'final' => true,
                ],
                28 => [
                    'text' => 'Сумма платежа слишком велика',
                    'name' => 'AMOUNT_TOO_BIG',
                    'final' => true,
                ],
                29 => [
                    'text' => 'Неверный ID транзакции во внешней системе',
                    'name' => 'BAD_TXN_ID',
                    'final' => true,
                ],
                30 => [
                    'text' => 'Отсутствует цифровая подпись запроса',
                    'name' => 'EMPTY_SIGNATURE',
                    'final' => true,
                ],
                31 => [
                    'text' => 'Неверная цифровая подпись',
                    'name' => 'WRONG_SIGNATURE',
                    'final' => true,
                ],
                32 => [
                    'text' => 'Пустой запрос',
                    'name' => 'EMPTY_REQUEST',
                    'final' => true,
                ],
                34 => [
                    'text' => 'Неверная подпись проекта-получателя перевода',
                    'name' => 'BAD_EXT_AUTH',
                    'final' => true,
                ],
                35 => [
                    'text' => 'Переводы между основными балансами для проекта запрещены',
                    'name' => 'DISABLE_BALANCE_TRANSFER',
                    'final' => true,
                ],
                36 => [
                    'text' => 'Запрос на проведение платежа в данной валюте запрещен',
                    'name' => 'CURRENCY_NOT_ALLOWED',
                    'final' => true,
                ],
                37 => [
                    'text' => 'Получатель платежа найден в санкционных списках',
                    'name' => 'SANCTION_BLOCKED',
                    'final' => true,
                ],
                96 => [
                    'text' => 'По платежу был выполнен рефанд (возврат)',
                    'name' => 'REFUND',
                    'final' => true,
                ],
                97 => [
                    'text' => 'Неверная дата истечения срока действия карты',
                    'name' => 'WRONG_EXPIRATION_DATE',
                    'final' => true,
                ],
                98 => [
                    'text' => 'Неверное имя держателя карты',
                    'name' => 'WRONG_CARDHOLDER_NAME',
                    'final' => true,
                ],
                99 => [
                    'text' => 'Платеж отменен',
                    'name' => 'CANCELED',
                    'final' => true,
                ],
                100 => [
                    'text' => 'Не прошла проверка на стороне провайдера',
                    'name' => 'PS_CHECK_FAILED',
                    'final' => true,
                ],
                101 => [
                    'text' => 'Номер телефона не из диапазона провайдера',
                    'name' => 'BAD_NUMBER_RANGE',
                    'final' => true,
                ],
                102 => [
                    'text' => 'Некорректный номер банковской карты',
                    'name' => 'BAD_CARD_NUMBER',
                    'final' => true,
                ],
                103 => [
                    'text' => 'Превышен лимит средств на стороне концентратора/ПС',
                    'name' => 'BAD_LIMITS',
                    'final' => true,
                ],
                104 => [
                    'text' => 'Не найден кошелек WebMoney',
                    'name' => 'WM_WALLET_NOT_FOUND',
                    'final' => true,
                ],
                108 => [
                    'text' => 'Некорректный e-mail',
                    'name' => 'INVALID_EMAIL',
                    'final' => true,
                ],
                109 => [
                    'text' => 'Некорректный номер телефона',
                    'name' => 'INVALID_PHONE',
                    'final' => true,
                ],
                200 => [
                    'text' => 'Общая ошибка при выплате в провайдера',
                    'name' => 'PS_PAY_FAILED',
                    'final' => false,
                ],
                201 => [
                    'text' => 'Платежи на этот счет запрещены',
                    'name' => 'PAYMENT_NOT_ALLOWED',
                    'final' => true,
                ],
                202 => [
                    'text' => 'Аккаунт-получатель платежа блокирован',
                    'name' => 'ACCOUNT_BLOCKED',
                    'final' => true,
                ],
                203 => [
                    'text' => 'Платеж отвергнут системой фрод-мониторинга',
                    'name' => 'LIMITS_EXCEEDED',
                    'final' => true,
                ],
                204 => [
                    'text' => 'Услуга не полностью сконфигурирована',
                    'name' => 'PS_NOT_CONFIGURED',
                    'final' => true,
                ],
                205 => [
                    'text' => 'Платеж не может быть проведен по региональному признаку',
                    'name' => 'UNAVAILABLE_FOR_REGION',
                    'final' => true,
                ],
                206 => [
                    'text' => 'Проект не полностью сконфигурирован',
                    'name' => 'PROJECT_NOT_CONFIGURED',
                    'final' => true,
                ],
                991 => [
                    'text' => 'Переданы неполные или неверные персональные данные',
                    'name' => 'INCOMPLETE_PERSONAL_INFO',
                    'final' => true,
                ],
                993 => [
                    'text' => 'Истекло время жизни инвойса',
                    'name' => 'INVOICE_LIFETIME_EXPIRED',
                    'final' => true,
                ],
                994 => [
                    'text' => 'Истек срок действия секретного ключа',
                    'name' => 'SECRET_KEY_EXPIRED',
                    'final' => true,
                ],
                995 => [
                    'text' => 'Доступ с текущего IP запрещен',
                    'name' => 'IP_NOT_WHITELISTED',
                    'final' => true,
                ],
                996 => [
                    'text' => 'Действие удалено',
                    'name' => 'DEPRECATED_ACTION',
                    'final' => true,
                ],
                997 => [
                    'text' => 'Выплаты в указанного провайдера отвергнуты шлюзом-получателем',
                    'name' => 'PS_UNAVAILABLE',
                    'final' => true,
                ],
                999 => [
                    'text' => 'Доступ запрещен',
                    'name' => 'FORBIDDEN',
                    'final' => true,
                ],
                1000 => [
                    'text' => 'Внутренняя ошибка системы',
                    'name' => 'INTERNAL_ERROR',
                    'final' => false,
                ],
            ],
            self::WAY_DEPOSIT => [
                0 => [
                    'text' => 'Зарегистрирован счёт, пользователь прошёл на страницу ПС',
                    'name' => 'In progress',
                    'final' => false,
                ],
                1 => [
                    'text' => 'Зарегистрирован счёт, пользователь прошёл на страницу ПС',
                    'name' => 'In progress',
                    'final' => false,
                ],
                3 => [
                    'text' => 'Возникла проблема при взаимодействии Система-Проект, требуется обращение к техническим специалистам Системы',
                    'name' => 'Warning',
                    'final' => false,
                ],
                4 => [
                    'text' => 'Возникла проблема при взаимодействии Система-Проект, требуется обращение к техническим специалистам Системы',
                    'name' => 'Warning',
                    'final' => false,
                ],
                5 => [
                    'text' => 'Платеж неуспешен. Денежные средства возвращены клиенту',
                    'name' => 'Fail',
                    'final' => true,
                ],
                6 => [
                    'text' => 'Возникла проблема при взаимодействии Система-Проект, требуется обращение к техническим специалистам Системы',
                    'name' => 'Warning',
                    'final' => false,
                ],
                7 => [
                    'text' => 'Платеж неуспешен. Денежные средства возвращены клиенту',
                    'name' => 'Fail',
                    'final' => true,
                ],
                9 => [
                    'text' => 'Платеж успешен',
                    'name' => 'Success',
                    'final' => true,
                ],
                10 => [
                    'text' => 'Возникла проблема при взаимодействии Система-Проект, требуется обращение к техническим специалистам Системы',
                    'name' => 'Warning',
                    'final' => false,
                ],
                12 => [
                    'text' => 'Возникла проблема при взаимодействии Система-Проект, требуется обращение к техническим специалистам Системы',
                    'name' => 'Warning',
                    'final' => false,
                ],
                13 => [
                    'text' => 'Возникла проблема при взаимодействии Система-Проект, требуется обращение к техническим специалистам Системы',
                    'name' => 'Warning',
                    'final' => false,
                ],
                14 => [
                    'text' => 'Платеж отменен',
                    'name' => 'Cancel',
                    'final' => true,
                ],
                16 => [
                    'text' => 'Зарегистрирован счёт, пользователь прошёл на страницу ПС',
                    'name' => 'In progress',
                    'final' => false,
                ],
                22 => [
                    'text' => 'Захолдированный платеж',
                    'name' => 'Hold',
                    'final' => false,
                ],
                24 => [
                    'text' => 'Успешный тестовый платеж. Не учитывается при расчёте балансов',
                    'name' => 'Success test',
                    'final' => true,
                ],
                25 => [
                    'text' => 'Захолдированный платеж',
                    'name' => 'Hold',
                    'final' => false,
                ],
            ],
            'pay_status' => [
                'new' => [
                    'text' => 'новая, не проведена',
                    'final' => false,
                ],
                'processing' => [
                    'text' => 'обрабатывается сейчас',
                    'final' => false,
                ],
                'pending' => [
                    'text' => 'в очереди, ожидает обработки',
                    'final' => false,
                ],
                'paid' => [
                    'text' => 'выплата проведена',
                    'final' => true,
                ],
                'error' => [
                    'text' => 'ошибка выплаты',
                    'final' => true,
                ],
                'canceled' => [
                    'text' => 'выплата отменена, средства возвращены на баланс проекта',
                    'final' => true,
                ],
                'expired' => [
                    'text' => 'превышен интервал ожидания для проведения транзакции',
                    'final' => false,
                ],
            ],
        ];

        return $status_lib[$way] ?? $status_lib;
    }
}