<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 27.11.18
 * Time: 15:42
 */

namespace pps\payment;

/**
 * Class SupportDenominationInterface
 *
 * If support of denomination needed
 * Example: platform need mlBTC, but payment system use only BTC
 * You should implement this interface in payment system extension and returm 1000 by getDenomination()
 * @package pps\payment
 */
interface SupportDenominationInterface
{

    /**
     * Return denomination coefficient
     * @return int
     */
    public function getDenomination(): int;
}