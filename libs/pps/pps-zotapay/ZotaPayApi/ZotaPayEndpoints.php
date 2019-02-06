<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 16:23
 */

namespace pps\zotapay\ZotaPayApi;

use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;

class ZotaPayEndpoints
{
    const TEST = 0;
    const LIVE = 1;

    const ACTION_ORDER = 'order';
    const ACTION_PAYOUT = 'payout';
    const ACTION_PAYOUT_STATUS = 'payout_status';
    const ACTION_ORDER_STATUS = 'order_status';

    const BASE_API_URL = 'https://gate.zotapay.com/paynet/api/';
    const TEST_BASE_API_URL = 'https://sandbox.zotapay.com/paynet/api/';

    /**
     * @param int $mode
     * @param string $action
     * @return string
     * @throws ZotaPayAPIException
     */
    public static function getEndPointUrl(int $mode, string $action): string
    {
        $basePath = '';
        switch ($mode) {
            case self::LIVE:
                $basePath = self::BASE_API_URL;
                break;
            case self::TEST:
                $basePath = self::TEST_BASE_API_URL;
                break;
            default:
                throw new ZotaPayAPIException("Mode '{$mode}' doesn`t exist.");
        }

        switch ($action) {
            case self::ACTION_ORDER:
                return $basePath.'v2/sale-form/';
            case self::ACTION_PAYOUT:
                return $basePath.'v2/payout/';
            case self::ACTION_ORDER_STATUS:
                return $basePath.'v2/status/';
            case self::ACTION_PAYOUT_STATUS:
                return $basePath.'v2/status/';
            default:
                throw new ZotaPayAPIException("Action '{$action}' doesn`t exist.");
                break;
        }
    }
}
