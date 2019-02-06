<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 17:25
 */

namespace pps\zotapay\ZotaPayApi\Requests\card;

use function GuzzleHttp\Psr7\build_query;
use GuzzleHttp\Psr7\Request;
use pps\zotapay\ZotaPayApi\Requests\BaseRequest;
use yii\helpers\ArrayHelper;

/**
 * Merchant order identifier. Mandatory. 128/String
 * @property string $client_orderid
 *
 * Merchant’s login. Mandatory. 20/String
 * @property string $login
 *
 * Card number of destination card. Mandatory if destination-card-ref-id ommited. Conditional. 16-19/String
 * @property string $destination_card_no
 *
 * Card reference id to destination card, obtained at Card Registration step. Mandatory
 * if destination-card-no ommited. Conditional. 20/Numeric
 * @property int $destination_card_ref_id
 *
 * Amount to be transfered. The amount has to be specified in the highest units with . delimiter.
 * For instance, 10.5 for USD means 10 US Dollars and 50 Cents Mandatory. 10/Numeric
 * @property int $amount
 *
 * Currency the transaction is charged in (three-letter currency code). Example of  valid parameter
 * values are: USD for US Dollar EUR for European Euro Mandatory. 3/String
 * @property string $currency
 *
 * Depending on the acquirer (e.g. for transfer amounts over 15000 rub): Receiver’s identity document
 * series (e.g. 4 digits for Russian Federation passport). Conditional*. 512/String
 * @property string $receiver_identity_document_series
 *
 * Depending on the acquirer (e.g. for transfer amounts over 15000 rub): Receiver’s identity document
 * number (e.g. 6 digits for Russian Federation passport). Conditional*. 512/String
 * @property string $receiver_identity_document_number
 *
 * Depending on the acquirer (e.g. for transfer amounts over 15000 rub): Receiver’s identity document
 * id. Possible values: 21 for Russian Federation passport or 31 for international passport. Conditional*. 512/String
 * @property string $receiver_identity_document_id
 *
 * Depending on the acquirer (e.g. for transfer amounts over 15000 rub): Receiver’s address Conditional*. 512/String
 * @property string $receiver_address1
 *
 * Depending on the acquirer (e.g. for transfer amounts over 15000 rub): Receiver’s city Conditional*. 512/String
 * @property string $receiver_city
 *
 * Order description Optional. 125/String
 * @property string $order_desc
 *
 * Customer’s IP address, included for fraud screening purposes. Optional. 20/String
 * @property string $ipaddress
 *
 * Sender first name Optional. 128/String
 * @property string $first_name
 *
 * Sender middle name Optional. 128/String
 * @property string $middle_name
 *
 * Sender last name Optional. 128/String
 * @property string $last_name
 *
 * Last four digits of the Sender’s social security number. Optional. 4/Numeric
 * @property int $ssn
 *
 * Sender date of birth, in the format MMDDYY. Optional. 8/Numeric
 * @property int $birthday
 *
 * Sender address line 1. Optional. 50/String
 * @property string $address1
 *
 * Sender city. Optional. 50/String
 * @property string $city
 *
 * Sender’s state. Please see Appendix A for a list of valid state codes. Optional. 2-3/String
 * @property string $state
 *
 * Sender ZIP code. Optional. 10/String
 * @property string $zip_code
 *
 * Sender country(two-letter country code). Please see Appendix B for a list of valid country codes. Optional. 2/String
 * @property string $country
 *
 * Sender full international phone number, including country code. Optional. 15/String
 * @property string $phone
 *
 * Sender full international cell phone number, including country code. Optional. 15/String
 * @property string $cell_phone
 *
 * Sender email address. Optional. 50/String
 * @property string $email
 *
 * Receiver first name. Optional. 128/String
 * @property string $receiver_first_name
 *
 * Receiver middle name. Optional. 128/String
 * @property string $receiver_middle_name
 *
 * Receiver last name. Optional. 128/String
 * @property string $receiver_last_name
 *
 * Receiver full international cell phone number, including country code. Optional. 128/String
 * @property string $receiver_phone
 *
 * Is receiver a resident? Optional. Boolean (true/false)
 * @property  $receiver_resident
 *
 * URL the transaction result will be sent to. Merchant may use this URL for custom processing of the transaction
 * completion, e.g. to collect sales data in Merchant’s database. See more details at Merchant Callbacks
 * Optional. 128/String
 * @property string $server_callback_url
 *
 * URL the cardholder will be redirected to upon completion of the transaction. Please note that the cardholder
 * will be redirected in any case, no matter whether the transaction is approved or declined. Optional. 250/String
 * @property string $redirect_url
 */
class CardPayoutRequest extends BaseRequest
{
    public $client_orderid;
    public $login;
    public $destination_card_no;
    public $destination_card_ref_id;
    public $amount;
    public $currency;
    public $receiver_identity_document_series;
    public $receiver_identity_document_number;
    public $receiver_identity_document_id;
    public $receiver_address1;
    public $receiver_city;
    public $order_desc;
    public $ipaddress;
    public $first_name;
    public $middle_name;
    public $last_name;
    public $ssn;
    public $birthday;
    public $address1;
    public $city;
    public $state;
    public $zip_code;
    public $country;
    public $phone;
    public $cell_phone;
    public $email;
    public $receiver_first_name;
    public $receiver_middle_name;
    public $receiver_last_name;
    public $receiver_phone;
    public $receiver_resident;
    public $server_callback_url;
    public $redirect_url;

    public function rules()
    {
        return [
            [$this->mandatoryFields, 'required'],
            [
                [
                    'client_orderid',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'receiver_first_name',
                    'receiver_middle_name',
                    'receiver_last_name',
                    'receiver_phone',
                    'server_callback_url',
                ],
                'string',
                'max' => 128
            ],

            // one of fields destination_card_ref_id or destination_card_no should be passed
            [
                'destination_card_ref_id',
                'required',
                'when' => function ($form) {
                    return empty($form->destination_card_no);
                },
                'skipOnError' => false,
                'message' => 'destination_card_ref_id or destination_card_no should be passed'
            ],
            [
                'destination_card_no',
                'required',
                'when' => function ($form) {
                    return empty($form->destination_card_ref_id);
                },
                'skipOnError' => false,
                'message' => 'destination_card_ref_id or destination_card_no should be passed'
            ],
            [
                'destination_card_ref_id',
                function ($attribute, $params) {
                    if (!empty($this->destination_card_ref_id) && !empty($this->destination_card_no)) {
                        $this->addError(
                            $attribute,
                            'Only one of destination_card_ref_id or destination_card_no should be passed'
                        );
                    }
                }
            ],

            [
                [
                    'first_name',
                    'last_name',
                    'address1',
                    'city',
                    'email',
                ],
                'string',
                'max' => 50
            ],
            [
                [
                    'receiver_identity_document_series',
                    'receiver_identity_document_number',
                    'receiver_identity_document_id',
                    'receiver_address1',
                    'receiver_city',
                ],
                'string',
                'max' => 512
            ],
            ['amount', 'number', 'max' => 10],
            ['zip_code', 'string', 'max' => 10],
            ['order_desc', 'string', 'max' => 125],
            ['destination_card_no', 'string', 'min' => 16, 'max' => 19],
            [['phone', 'cell_phone'], 'string', 'max' => 15],
            ['state', 'string', 'min' => 2, 'max' => 3],
            ['country', 'string', 'min' => 2, 'max' => 2],
            ['destination_card_ref_id', 'number', 'min' => 20, 'max' => 20],
            ['redirect_url', 'string', 'max' => 250],
            [['ipaddress', 'login'], 'string', 'max' => 20],
            ['currency', 'string', 'min' => 3, 'max' => 3],
            ['ssn', 'number', 'min' => 4, 'max' => 4],
            [['address1', 'city', 'email'], 'string', 'max' => 50],
            [['receiver_resident'], 'boolean'],
            ['birthday', 'number', 'min' => 8, 'max' => 8],
        ];
    }

    protected $mandatoryFields = [
        'client_orderid',
        'login',
        'amount',
        'currency',
    ];

    protected static function getFieldsMapping(): array
    {
        return [
            'destination_card_no' => 'destination-card-no',
            'destination_card_ref_id' => 'destination-card-ref-id',
        ];
    }

    public function getRequestBody(): array
    {
        if (empty($this->requestBody)) {
            $oauthDataArray = [
                'oauth_consumer_key' => $this->conf->getClientLogin(),
                'oauth_nonce' => \Yii::$app->security->generateRandomString(11),
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => time(),
                'oauth_version' => '1.0',
            ];

            $requestData = ArrayHelper::merge($this->toArray(), $oauthDataArray);
            ksort($requestData);
            $this->requestBody = $requestData;
        }
        return $this->requestBody;
    }

    /**
     * @return Request
     * @throws \pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException
     */
    public function prepareRequest(): Request
    {
        $requestData = $this->getRequestBody();
        $hashString = urlencode('POST&' . $this->getRequestUrl() . '&' . build_query($requestData));
        $hmacSecret = $this->conf->getControlKey() . '&';

        $oauthDataArray['oauth_signature'] = urlencode(
            base64_encode(hash_hmac(MHASH_SHA1, $hashString, $hmacSecret))
        );

        $authHeader = array_reduce(
            array_keys($oauthDataArray),
            function ($carry, $key) use ($oauthDataArray) {
                return $carry . ',' . $key . '="' . $oauthDataArray[$key] . '"';
            },
            'OAuth realm="",'
        );

        return new Request(
            'POST',
            $this->getRequestUrl(),
            ['Authorization' => $authHeader],
            build_query($requestData)
        );
    }
}
