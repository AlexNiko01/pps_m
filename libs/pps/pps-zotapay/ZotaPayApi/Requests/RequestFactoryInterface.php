<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 14.08.18
 * Time: 12:05
 */

namespace pps\zotapay\ZotaPayApi\Requests;

interface RequestFactoryInterface
{
    public static function gerRequest(): ZotaPayRequestInterface;
}
