<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 14:45
 */

namespace pps\zotapay\ZotaPayApi\Responses\card;

use pps\zotapay\ZotaPayApi\Responses\BaseResponse;

/**
 * The type of response. May be async-form-response, validation-error, error.
 * If type equals validation-error or error, error-message and error-code parameters contain error details. .
 * @property  $type
 *
 * Order id assigned to the order by Zotapay .
 * @property  $paynet_order_id
 *
 * Merchant order id .
 * @property  $merchant_order_id
 *
 * Unique number assigned by Zotapay server to particular request from the Merchant. .
 * @property  $serial_number
 *
 * If status is declined or error this parameter contains the reason for decline or error details .
 * @property  $error_message
 *
 * The error code in case of declined or error status .
 * @property  $error_code
 */
class CardPayoutResponse extends BaseResponse
{
    public $type;
    public $paynet_order_id;
    public $merchant_order_id;
    public $serial_number;
    public $error_message;
    public $error_code;

    public function rules()
    {
        return [
            [[
                'type',
                'paynet_order_id',
                'merchant_order_id',
                'serial_number',
                'error_message',
                'error_code',
            ], 'trim']
        ];
    }
}
