<?php

namespace pps\payment;

/**
 * Interface ICryptoCurrency
 * @package pps\payment
 */
interface ICryptoCurrency
{
    /**
     * Get new address for user
     * @param $buyer_id
     * @param $callback_url
     * @param $brand_id
     * @param $currency
     * @return array
     */
    public function getAddress($buyer_id, $callback_url, $brand_id, $currency): array;

    /**
     * Fill incoming transaction
     * @param $paymentSystemId
     * @param $userAddress
     * @param $receiveData
     * @return array|bool
     */
    public static function fillTransaction($paymentSystemId, $userAddress, $receiveData);
}