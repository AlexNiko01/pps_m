<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 16:30
 */

namespace pps\zotapay\ZotaPayApi;

class ZotaPayPaymentMethod
{
    const CARD = 'card';
    const BANK = 'bank';

    const LIST = [
        self::CARD,
        self::BANK,
    ];
}
