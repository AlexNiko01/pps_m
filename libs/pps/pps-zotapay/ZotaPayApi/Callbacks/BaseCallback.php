<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 25.07.18
 * Time: 11:05
 */

namespace pps\zotapay\ZotaPayApi\Callbacks;

use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayValidationException;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use yii\base\Model;

/**
 * Base Callback Object
 * See Status List for details. .
 * @property  $status
 *
 * Merchant order identifier, client_orderid .
 * @property  $merchant_order
 *
 * Merchant order identifier .
 * @property  $client_orderid
 *
 * Zotapay transaction id .
 * @property  $orderid
 *
 * Transaction type, sale reversal chargeback .
 * @property  $type
 *
 * Transaction amount .
 * @property  $amount
 *
 * Payment descriptor of the gate through which the transaction has been processed. .
 * @property  $descriptor
 *
 * Error Code .
 * @property  $error_code
 *
 * Error Message .
 * @property  $error_message
 *
 * Cardholder Name .
 * @property  $name
 *
 * Customer’s email .
 * @property  $email
 *
 * Authorization approval code, if any .
 * @property  $approval_code
 *
 * Last four digits of customer credit card number. .
 * @property  $last_four_digits
 *
 * Bank BIN of customer credit card. .
 * @property  $bin
 *
 * Type of customer credit card (VISA, MASTERCARD, etc). .
 * @property  $card_type
 *
 * Processing gate support partial reversal (enabled or disabled). .
 * @property  $gate_partial_reversal
 *
 * Processing gate support partial capture (enabled or disabled). .
 * @property  $gate_partial_capture
 *
 * Reason code for chargebak or fraud operation. .
 * @property  $reason_code
 *
 * Bank Receiver Registration Number. .
 * @property  $processor_rrn
 *
 * Comment in case of Return transaction .
 * @property  $comment
 *
 * Current balance for merchants registered in Rapida system (only if balance check active) .
 * @property  $rapida_balance
 *
 * Checksum is used to ensure that it is Zotapay (and not a fraudster) that initiates the callback for a particular
 * Merchant. This is SHA_1 checksum of the concatenation status + orderid + merchant_order + merchant_control.
 * The callback script MUST check this parameter by comparing it to SHA_1 checksum of the above concatenation.
 * See Callback authorization through control parameter for more details about generating control checksum. .
 * @property  $control
 *
 * Reserved .
 * @property  $merchantdata
 */
class BaseCallback extends Model
{
    public $status;
    public $merchant_order;
    public $client_orderid;
    public $orderid;
    public $type;
    public $amount;
    public $descriptor;
    public $error_code;
    public $error_message;
    public $name;
    public $email;
    public $approval_code;
    public $last_four_digits;
    public $bin;
    public $card_type;
    public $gate_partial_reversal;
    public $gate_partial_capture;
    public $reason_code;
    public $processor_rrn;
    public $comment;
    public $rapida_balance;
    public $control;
    public $merchantdata;


    protected $originRequest = [];

    /** @var ZotaPayConfig $conf */
    protected $conf;

    public function rules()
    {
        return [
            [['amount', 'merchant_order', 'status', 'orderid', 'control'], 'required'],
            ['control', 'validateControl']
        ];
    }

    /**
     * BaseCallback constructor.
     * @param ZotaPayConfig $config
     * @param array $request
     * @throws ZotaPayValidationException
     */
    public function __construct(ZotaPayConfig $config, array $request)
    {
        parent::__construct();
        $this->conf = $config;
        $this->originRequest = $request;
        $this->setAttributes($request, false);
        if (!$this->validate()) {
            throw new ZotaPayValidationException('Wrong parameters given: ' . print_r($this->getErrors(), true));
        };
    }

    public function setAttributes($values, $safeOnly = true)
    {
        $preparedValues = [];
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $preparedValues[strtr($key, ['-' => '_'])] = $value;
            }
        }
        parent::setAttributes($preparedValues, $safeOnly);
    }

    public static function getTransactionId(array $callbackData)
    {
        if (array_key_exists('merchant_order', $callbackData)) {
            return $callbackData['merchant_order'];
        }
        return 0;
    }

    public function validateControl($attribute, $params)
    {
        $calculatedString = sha1(
            $this->status
            . $this->orderid
            . $this->merchant_order
            . $this->conf->getControlKey()
        );

        if ($this->control !== $calculatedString) {
            $this->addError($attribute, 'Controll code is not valid');
        }
    }

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
}
