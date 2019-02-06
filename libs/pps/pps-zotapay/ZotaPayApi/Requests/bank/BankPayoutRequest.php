<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 17:25
 */

namespace pps\zotapay\ZotaPayApi\Requests\bank;

use function GuzzleHttp\Psr7\build_query;
use GuzzleHttp\Psr7\Request;
use pps\zotapay\ZotaPayApi\Requests\BaseRequest;
use yii\helpers\ArrayHelper;

/**
 * Account Number Mandatory. 24/String
 * @property string $account_number
 * Currency the transaction is charged in (three-letter currency code). Sample values are: USD for US Dollar
 * EUR for European Euro Mandatory. 3/String
 * @property string $currency
 * Amount to be charged. The amount has to be specified in the highest units with . delimiter. For instance,
 * 10.5 for USD means 10 US Dollars and 50 Cents Mandatory. 10/Numeric
 * @property int $amount
 * Merchant order identifier. Mandatory. 128/String
 * @property string $client_orderid
 * Bank Name Mandatory. 255/String
 * @property string $bank_name
 * Bank Branch Name Mandatory. 255/String
 * @property string $bank_branch
 * Receiver first name Conditional*. 50/String
 * @property string $first_name
 * Receiver last name Conditional*. 50/String
 * @property string $last_name
 * Receiver phone Conditional*. 50/String
 * @property string $phone
 * Customer’s E-mail Conditional*. 128/String
 * @property string $email
 * Customer’s IP address (IPv4 or IPv6) Conditional*. 7-45/String
 * @property string $ipaddress
 * Payout purpose Conditional*. 128/String
 * @property string $purpose
 * Bank code Conditional*. 3/String
 * @property string $bank_code
 * Bank address Conditional*. 255/String
 * @property string $bank_address1
 * Bank postal ZIP code Conditional*. 255/String
 * @property string $bank_zip_code
 * Routing number used to identify specific bank branches in China Conditional*. 16/String
 * @property string $routing_number
 * Customer’s credit card number. Conditional*. 20/Numeric
 * @property int $credit_card_number
 * Customer’s full name, as printed on the card Conditional*. 128/String
 * @property string $card_printed_name
 * Credit card expiration month Conditional*. 2/Numeric
 * @property int $expire_month
 * Credit card expiration year Conditional*. 4/Numeric
 * @property int $expire_year
 * Customer’s CVV2 code. CVV2 (Card Verification Value) is a three- or four-digit number AFTER the credit card number
 * in the signature area of the card. Conditional*. 3-4/Numeric
 * @property int $cvv2
 * Bank province Optional. 255/String
 * @property string $bank_province
 * Bank area Optional. 255/String
 * @property string $bank_area
 * Bank account Optional. 128/String
 * @property string $account_name
 * Brief order description Optional. 64/String
 * @property string $order_desc
 * URL the transaction result will be sent to. Merchant may use this URL for custom processing of the transaction
 * completion, e.g. to collect sales data in Merchant’s database.
 * See more details at Merchant Callbacks Optional. 128/String
 * @property string $server_callback_url

 */
class BankPayoutRequest extends BaseRequest
{
    public $account_number;
    public $currency;
    public $amount;
    public $client_orderid;
    public $bank_name;
    public $bank_branch;
    public $first_name;
    public $last_name;
    public $phone;
    public $email;
    public $ipaddress;
    public $purpose;
    public $bank_code;
    public $bank_address1;
    public $bank_zip_code;
    public $routing_number;
    public $credit_card_number;
    public $card_printed_name;
    public $expire_month;
    public $expire_year;
    public $cvv2;
    public $bank_province;
    public $bank_area;
    public $account_name;
    public $order_desc;
    public $server_callback_url;

    private $oAuthData = [];

    public function rules()
    {
        return [
            [$this->mandatoryFields, 'required'],
            [
                [
                    'client_orderid',
                    'card_printed_name',
                    'account_name',
                    'server_callback_url',
                    'purpose',
                    'email',
                ],
                'string',
                'max' => 128
            ],
            [
                [
                    'bank_name',
                    'bank_branch',
                    'bank_address1',
                    'bank_zip_code',
                    'bank_province',
                    'bank_area',
                ],
                'string',
                'max' => 255
            ],
            [['first_name', 'last_name', 'phone',], 'string', 'max' => 50],
            ['amount', 'number', 'max' => 10],
            ['credit_card_number', 'number', 'max' => 20],
            ['expire_month', 'number', 'min' => 1, 'max' => 12],
            ['expire_year', 'number', 'min' => 2019, 'max' => 3000],
            ['cvv2', 'number', 'min' => 1, 'max' => 9999],
            [['currency', 'bank_code'], 'string', 'length' => 3],
            ['ipaddress', 'ip'],
            ['routing_number', 'string', 'max' => 16],
            ['order_desc', 'string', 'max' => 64],
        ];
    }

    protected $mandatoryFields = [
        'account_number',
        'currency',
        'amount',
        'client_orderid',
        'bank_name',
        'bank_branch',
    ];

    /**
     * @return array
     * @throws \yii\base\Exception
     */
    public function getRequestBody(): array
    {
        if (empty($this->requestBody)) {
            $requestData = ArrayHelper::merge($this->toArray(), $this->getOAuthData());
            ksort($requestData);
            $this->requestBody = $requestData;
        }
        return $this->requestBody;
    }

    /**
     * @return Request
     * @throws \pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException
     * @throws \yii\base\Exception
     */
    public function prepareRequest(): Request
    {
        $requestData = $this->getRequestBody();
        $hashString = 'POST&' . urlencode($this->getRequestUrl()) . '&' . urlencode(build_query($requestData));
        $hmacSecret = $this->conf->getControlKey() . '&';

        $oauthDataArray = $this->getOAuthData();

        $oauthDataArray['oauth_signature'] = urlencode(
            base64_encode(mhash(MHASH_SHA1, $hashString, $hmacSecret))
        );

        $authHeader = array_reduce(
            array_keys($oauthDataArray),
            function ($carry, $key) use ($oauthDataArray) {
                return $carry . ',' . $key . '="' . $oauthDataArray[$key] . '"';
            },
            'OAuth realm=""'
        );

        return new Request(
            'POST',
            $this->getRequestUrl(),
            [
                'Content-type' => 'application/x-www-form-urlencoded',
                'Authorization' => $authHeader,
            ],
            build_query($requestData)
        );
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     */
    private function getOAuthData(): array
    {
        if ($this->oAuthData === []) {
            $this->oAuthData = [
                'oauth_consumer_key' => $this->conf->getClientLogin(),
                'oauth_nonce' => \Yii::$app->security->generateRandomString(11),
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => time(),
                'oauth_version' => '1.0',
            ];
        }
        return $this->oAuthData;
    }
}
