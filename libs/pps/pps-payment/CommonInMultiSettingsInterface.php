<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 14.11.18
 * Time: 8:24
 */

namespace pps\payment;

/**
 * Class CommonInMultiSettingsInterface
 *
 * Used for settings model of payment system extensions which has different settings depend on currencies
 * and common settings for all currencies
 * @package pps\payment
 */
interface CommonInMultiSettingsInterface extends MultiSettingsInterface
{
    const COMMON_NAME = 'common';

    const COMMON_PARAMS_SCENARIO = 'common_params_scenario';

    const INDIVIDUAL_PARAMS_SCENARIO = 'individual_params_scenario';
    /**
     * Array with list of settings which is common for all currencies
     * @return array
     */
    public function commonSettings(): array;
}
