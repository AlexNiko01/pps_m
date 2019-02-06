<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 14.11.18
 * Time: 8:24
 */

namespace pps\payment;

/**
 * Class MultiSettingsInterface
 *
 * Used for settings model of payment system extensions which has different settings depend on currencies
 * @package pps\payment
 */
interface MultiSettingsInterface
{
    /**
     * Array with list of currencies which has own settings
     * @return array
    */
    public function currencies(): array;
}
