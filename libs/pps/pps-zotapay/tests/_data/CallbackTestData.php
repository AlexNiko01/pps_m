<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 20.08.18
 * Time: 11:01
 */

namespace pps\zotapay\tests\_data;

class CallbackTestData
{
    public static function successCallbackData()
    {
        return [
            'status' => 'approved',
            'merchant_order' => 'invoice-1',
//            'client_orderid',
            'orderid' => '123',
//            'type' => '',
            'amount' => 100,
//            'descriptor',
//            'error_code',
//            'error_message',
//            'name',
//            'email',
//            'approval-code',
//            'last-four-digits',
//            'bin',
            'card-type' => 'VISA',
//            'gate-partial-reversal',
//            'gate-partial-capture',
//            'reason-code',
//            'processor-rrn',
//            'comment',
//            'rapida-balance',
            'control' => '5bc8ee48f9ba37c0fd1e0b052a9bc105c6df87e1',
    //        'merchantdata',

        ];
    }

}