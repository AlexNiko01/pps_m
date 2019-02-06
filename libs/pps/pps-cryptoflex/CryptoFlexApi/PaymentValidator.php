<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 10:17
 */

namespace pps\cryptoflex\CryptoFlexApi;

use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexValidationException;

class PaymentValidator
{

    const SECRET_KEY_MIN_LENGTH = 15;

    /**
     * @param $value
     * @param $label
     * @param bool $unsigned
     * @throws CryptoFlexValidationException
     */
    public static function amountValidate($value, $label, $unsigned = true)
    {
        if (!is_numeric($value) || ($unsigned && $value < 0.003)) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid amount value");
        }
    }

    /**
     * @param $value
     * @param $label
     * @param int $min_length
     * @param int $max_length
     * @throws CryptoFlexValidationException
     */
    public static function stringValidate($value, $label, $min_length = 1, $max_length = 256)
    {
        if (!is_string($value)) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid string value");
        }
        self::validateRange($value, $label, $min_length, $max_length);
    }

    /**
     * @param $value
     * @param $label
     * @param int $min_length
     * @param int $max_length
     * @throws CryptoFlexValidationException
     */
    public static function validateRange($value, $label, $min_length = 1, $max_length = 256)
    {
        if (strlen($value) < $min_length) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid length. Minimum {$min_length}");
        }
        if (strlen($value) > $max_length) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid length. Maximum {$max_length}");
        }
    }

    /**
     * @param $value
     * @param $label
     * @throws CryptoFlexValidationException
     */
    public static function digitValidate($value, $label)
    {
        $value = (string)$value;
        if (!ctype_digit($value)) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid digit value");
        }
    }

    /**
     * @param $value
     * @param $label
     * @return bool
     * @throws CryptoFlexValidationException
     */
    public static function emailValidate($value, $label)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new CryptoFlexValidationException("'{$label}' has not a fully qualified email");
        }
        return true;
    }

    /**
     * @param $value
     * @param $label
     * @param bool $unsigned
     * @return bool
     * @throws CryptoFlexValidationException
     */
    public static function integerValidate($value, $label, $unsigned = true)
    {
        if ((!is_integer($value) && !ctype_digit($value)) || ($unsigned && intval($value) <= 0)) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid integer value");
        }
        return true;
    }

    /**
     * @param $value
     * @param $label
     * @param int $min_value
     * @param int $max_value
     * @throws CryptoFlexValidationException
     */
    public static function validateIntegerRange($value, $label, $min_value = 1, $max_value = 10000)
    {
        if (intval($value) < $min_value) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid value. Minimum {$min_value}");
        }
        if (intval($value) > $max_value) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid value. Maximum {$max_value}");
        }
    }

    /**
     * @param $value
     * @param $label
     * @return bool
     * @throws CryptoFlexValidationException
     */
    public static function urlValidate($value, $label)
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false
            || !in_array(parse_url($value, PHP_URL_SCHEME), ["http", "https"])
        ) {
            throw new CryptoFlexValidationException("'{$label}' has not a fully qualified URL");
        }
        return true;
    }

    /**
     * @param $value
     * @param $label
     * @return bool
     * @throws CryptoFlexValidationException
     */
    public static function xmlValidate($value, $label)
    {
        libxml_use_internal_errors(true);
        if (simplexml_load_string($value) === false) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid xml value");
        }
        return true;
    }

    /**
     * @param $value
     * @param $label
     * @return bool
     * @throws CryptoFlexValidationException
     */
    public static function secretValidate($value, $label)
    {
        if (!is_string($value) || strlen($value) < self::SECRET_KEY_MIN_LENGTH) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid value");
        }
        return true;
    }

    /**
     * @param $value
     * @param $haystack
     * @param $label
     * @throws CryptoFlexValidationException
     */
    public static function allowValidate($value, $haystack, $label)
    {
        if (!in_array($value, $haystack, true)) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid value");
        }
    }

    /**
     * @param $value
     * @param $label
     * @throws CryptoFlexValidationException
     */
    public static function phoneValidate($value, $label)
    {
        if (!preg_match('/(^\+[0-9]{5,15}$)|(^[0-9]{5,15}$)/', $value)) {
            throw new CryptoFlexValidationException("'{$label}' has not a valid value");
        }
    }


}
