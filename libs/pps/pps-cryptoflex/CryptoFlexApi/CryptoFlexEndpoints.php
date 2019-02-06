<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 16:23
 */

namespace pps\cryptoflex\CryptoFlexApi;

use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexAPIException;

class CryptoFlexEndpoints
{
    const TEST = 0;
    const LIVE = 1;

    const ACTION_ORDER = 'order';
    const ACTION_PAYOUT = 'withdraw';
    const ACTION_PAYOUT_STATUS = 'withdrawal_status';
    const ACTION_WALLET_BALANCE = 'check_balance';
    const ACTION_WALLET_CREATE = 'create_wallet';

    const BASE_API_URL = 'https://api.cryptoflex.co/';
    const TEST_BASE_API_URL = 'https://api.cryptoflex.co/';


    /**
     * @param int $mode
     * @param string $action
     * @return string
     * @throws CryptoFlexAPIException
     */
    public static function getEndPointUrl(int $mode, string $action): string
    {
        switch ($mode) {
            case self::LIVE:
                $basePath = self::BASE_API_URL;
                break;
            case self::TEST:
                $basePath = self::TEST_BASE_API_URL;
                break;
            default:
                throw new CryptoFlexAPIException("Mode '{$mode}' doesn`t exist.");
        }

        switch ($action) {
            case self::ACTION_ORDER:
                return $basePath . 'create_invoice';
            case self::ACTION_PAYOUT:
                return $basePath . 'withdraw';
            case self::ACTION_PAYOUT_STATUS:
                return $basePath . 'get_withdrawal_status';
            case self::ACTION_WALLET_BALANCE:
                return $basePath . 'check_balance';
            case self::ACTION_WALLET_CREATE:
                return $basePath . 'create_wallet';
            default:
                throw new CryptoFlexAPIException("Action '{$action}' doesn`t exist.");
                break;
        }
    }
}
