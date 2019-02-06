<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 16.08.18
 * Time: 10:58
 */

namespace pps\zotapay\ZotaPayApi\Responses;

class NullResponse extends BaseResponse
{
    public $type = 'error';
    public $error_message = 'Response has not retrieved';
    public $error_code = 'error';
}