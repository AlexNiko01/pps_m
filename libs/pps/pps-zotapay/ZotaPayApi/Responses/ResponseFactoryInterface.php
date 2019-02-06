<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 14.08.18
 * Time: 12:05
 */

namespace pps\zotapay\ZotaPayApi\Responses;

use pps\zotapay\ZotaPayApi\ZotaPayConfig;

interface ResponseFactoryInterface
{
    public static function getResponse(ZotaPayConfig $apiConfig, array $params = []): ZotaPayResponseInterface;
}
