<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 18.07.18
 * Time: 11:38
 */

namespace pps\cryptoflex\CryptoFlexApi;

class CryptoFlexStatuses
{
    const STATUS_WITHDRAW_NEW = 1;
    const STATUS_WITHDRAW_IN_PROCESS = 2;
    const STATUS_WITHDRAW_SUCCESSFUL = 3;
    const STATUS_WITHDRAW_REJECTED = 4;
    const STATUS_WITHDRAW_INTERNAL_ERROR = 5;
    const STATUS_WITHDRAW_CRYPTO_FLEX_REJECTED = 6;

    /**
     * Поступило на транзитный адрес (после необходимого числа подтверждений сети confirmations_trans)
     */
    const STATUS_INVOICE_PAID = 1;

    /**
     * Доступно на конечном адресе (после необходимого числа подтверждений сети confirmations_payout).
     */
    const STATUS_INVOICE_SUCCESSFUL = 2;

    /**
     * транзакция отменена из-за недостатка суммы для перевода на payout_address.
     */
    const STATUS_INVOICE_REJECTED = 4;

    const STATUS_TRANSACTION_ERROR_SUCCESS = 0;
    const STATUS_TRANSACTION_ERROR_WITHDRAWAL_IS_NOT_UNIQUE = 20;
    const STATUS_TRANSACTION_ERROR_WITHDRAWAL_DOES_NOT_EXIST = 21;
    const STATUS_TRANSACTION_ERROR_WALLET_DOES_NOT_EXIST = 50;
    const STATUS_TRANSACTION_ERROR_UNKNOWN = 2000;
    const STATUS_TRANSACTION_ERROR_INTERNAL = 2001;


    /**
     * @return array
     */
    protected static function getFinalWithdrawStatuses(): array
    {
        return [
            self::STATUS_WITHDRAW_SUCCESSFUL,
            self::STATUS_WITHDRAW_REJECTED,
            self::STATUS_WITHDRAW_CRYPTO_FLEX_REJECTED,
        ];
    }

    /**
     * @param $status
     * @return bool
     */
    public static function isFinalWithdrawStatus($status): bool
    {
        return \in_array($status, self::getFinalWithdrawStatuses());
    }

    /**
     * @return array
     */
    protected static function getFinalDepositStatuses(): array
    {
        return [
            self::STATUS_INVOICE_SUCCESSFUL,
            self::STATUS_INVOICE_REJECTED,
        ];
    }

    /**
     * @param $status
     * @return bool
     */
    public static function isFinalDepositStatus($status): bool
    {
        return \in_array($status, self::getFinalDepositStatuses());
    }
}
