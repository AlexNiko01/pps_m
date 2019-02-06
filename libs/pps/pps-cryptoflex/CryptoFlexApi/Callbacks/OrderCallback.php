<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 25.07.18
 * Time: 11:05
 */

namespace pps\cryptoflex\CryptoFlexApi\Callbacks;

use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexValidationException;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Base Callback Object
 *
 * уникальный идентификатор счета в системе CryptoFlex, в формате hash
 * @property string $invoice_id.
 *
 *  идентификатор на стороне мерчанта
 * @property string $partner_invoice_id
 *
 * сгенерированый транзитный адрес для приёма платежа в системе CryptoFlex используется один раз для одной
 * сущности Invoice
 * @property string $address
 *
 * необходимый мерчанту адрес (конечный), на который будут пересылаться средства
 * @property string $payout_address
 *
 * криптовалюта, в которой должен оплатить счет плательщик: ETH, BTC, BCH, LTC, DASH
 * @property string $crypto_currency
 *
 * число принятых подтверждений платежа в сети от плательщика на транзитный адрес - по умолчанию - для ETH,
 * DASH - 6 подтверждений, для BTC, BCH, LTC - 3 подтверждения
 * @property int $confirmations_trans
 *
 * число принятых подтверждений платежа в сети от транзитного на конечный адрес - по умолчанию -
 * для DASH - 3 подтверждения, для ETH, BTC, BCH, LTC - 2 подтверждения
 * @property int $confirmations_payout
 *
 * уровень комиссии сети high, medium и low - по умолчанию - medium
 * @property string $fee_level
 *
 * время и дата получения платежа на транзитный адрес, получили необходимое кол. подтверждений
 * confirmations_trans 2018-07-04 14:42:49.
 * @property  string $date_transit
 *
 * время и дата получения платежа на конечный адрес - пусто, если callback со status = 1 2018-07-04 15:01:26.
 * @property string $date_receive
 *
 * сумма полученная на транзитный адрес 0.0099328.
 * @property float $amount
 *
 * значение рассчитанное при отправке платежа на конечный адрес 0.0000441.
 * @property float $fee_value
 *
 * статус платежа 2.
 * @property int $status
 *
 * уникальный номер транзакции, при получении средств на транзитный адрес, формируется на стороне сервиса
 * CryptoFlex 0xa766231740051f52de9e6652db2e537ded1c671f56788285192583edfd928023.
 * @property string $transaction_id
 *
 * подпись для валидации колбэка hash256.
 * @property string $sign
 */
class OrderCallback extends Model
{

    public $invoice_id;
    public $partner_invoice_id;
    public $address;
    public $payout_address;
    public $crypto_currency;
    public $confirmations_trans;
    public $confirmations_payout;
    public $fee_level;

    public $date_transit;
    public $date_receive;
    public $amount;
    public $fee_value;
    public $status;
    public $transaction_id;
    public $sign;

    protected $originRequest = [];

    /** @var CryptoFlexConfig $conf */
    protected $conf;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['sign', 'required'],
            [
                [
                    'invoice_id',
                    'partner_invoice_id',
                    'address',
                    'payout_address',
                    'crypto_currency',
                    'fee_level',
                    'transaction_id'
                ],
                'string'
            ],
            [['confirmations_payout', 'confirmations_trans', 'status',], 'integer'],
            [
                ['fee_value', 'amount'],
                'filter',
                'filter' =>
                    function ($attribute) {
                        return (float)$attribute;
                    }
            ],
            [['amount', 'fee_value'], 'number'],
            [['date_receive', 'date_transit'], 'string'],
            ['sign', 'validateControl']
        ];
    }

    /**
     * BaseCallback constructor.
     * @param CryptoFlexConfig $config
     * @param array $request
     * @throws CryptoFlexValidationException
     */
    public function __construct(CryptoFlexConfig $config, array $request)
    {
        parent::__construct();
        $this->conf = $config;
        $this->originRequest = $request;
        $this->setAttributes($request, false);
        if (!$this->validate()) {
            throw new CryptoFlexValidationException('Wrong parameters given: ' . print_r($this->getErrors(), true));
        };
    }

    /**
     * @param array $callbackData
     * @return int|mixed
     */
    public static function getTransactionId(array $callbackData)
    {
        if (array_key_exists('partner_invoice_id', $callbackData)) {
            return $callbackData['partner_invoice_id'];
        }
        return 0;
    }

    public function validateControl($attribute)
    {
        if ($this->sign !== $this->generateSign()) {
            $this->addError($attribute, 'Sign is not valid');
        }
    }

    /**
     * @return string
     */
    public function getExternalTransactionId(): string
    {
        return ltrim($this->transaction_id, '0x');
    }

    /**
     * @return array
     */
    public function fields()
    {
        $fields = [];
        foreach ($this->attributes() as $propertyName) {
            if (!empty($this->$propertyName)) {
                $fields[] = $propertyName;
            }
        }
        return array_combine($fields, $fields);
    }

    /**
     * @return string
     */
    protected function generateSign(): string
    {
        $fieldsToSign = array_filter(
            $this->originRequest,
            function ($val) {
                return !($val === 'none' || $val == '');
            }
        );

        ArrayHelper::remove($fieldsToSign, 'sign');
        ksort($fieldsToSign, SORT_STRING);
        $stringToSign = implode(':', $fieldsToSign) . $this->conf->getSecretKey();

        $hash = hash('sha256', $stringToSign);

        if (YII_ENV === 'dev') {
            \Yii::info('String to sign: ' . $stringToSign, 'payment-cryptoflex-info');
            \Yii::info('Sign: ' . $hash, 'payment-cryptoflex-info');
        }

        return $hash;
    }

    public function __toString()
    {
        return json_encode($this->toArray());
    }
}
