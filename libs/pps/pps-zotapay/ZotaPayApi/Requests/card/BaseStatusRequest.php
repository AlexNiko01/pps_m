<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 17:25
 */

namespace pps\zotapay\ZotaPayApi\Requests\card;

use pps\zotapay\ZotaPayApi\Requests\BaseRequest;

/**
 * Merchant login name .
 * @property  $login
 *
 * Merchant order identifier of the transaction for which the status is requested .
 * @property  $client_orderid
 *
 * Order id assigned to the order by Zotapay .
 * @property  $orderid
 *
 * Checksum used to ensure that it is Zotapay (and not a fraudster) that initiates the callback for
 * a particular Merchant. This is SHA-1 checksum of the concatenation login + client-order-id
 * + paynet-order-id + merchant-control. See Order status API call authorization through control parameter
 * for more details about generating control checksum. .
 * @property  $control
 *
 * Serial number assigned to the specific request by Zotapay.
 * If this field exist in status request, status response return for this specific request.
 * @property  $by_request_sn
 */

class BaseStatusRequest extends BaseRequest
{
    public $login;
    public $client_orderid;
    public $orderid;
    public $control;
    public $by_request_sn; // нужно конвертировать в by-request-sn


    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [
                ['login', 'client_orderid', 'orderid', 'control', 'by_request_sn'], 'required'
            ],
        ];
    }

    protected static function getFieldsMapping(): array
    {
        return [
            'by_request_sn' => 'by-request-sn',
        ];
    }

    //заполняем поле с контрольной суммой

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        $checkSum = sha1(
            $this->conf->getClientLogin()
            . $this->client_orderid
            . $this->orderid
            . $this->conf->getControlKey()
        );
        $this->control = $checkSum;
        $this->login = $this->conf->getClientLogin();
        return parent::beforeValidate();
    }
}
