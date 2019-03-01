<?php

namespace common\components\helpers;

use yii\helpers\ArrayHelper;

/**
 * Class Restructuring
 * @package backend\helpers
 */
class Restructuring
{
    /**
     * @param $ps
     * @return array
     */
    public static function firstCategory(array $ps): array
    {
        $currencyKey = [];
        foreach ($ps as $methods) {
            foreach ($methods as $method) {
                foreach ($method as $field) {
                    $currencyKey = ArrayHelper::merge($currencyKey, $field['currencies']);
                }
            }
        }
        $currencyKeyUnique = array_unique($currencyKey);

        $newPs = [];
        foreach ($currencyKeyUnique as $currency) {
            foreach ($ps as $key => $methods) {
                foreach ($methods as $method) {
                    foreach ($method as $field) {
                        if (empty($field)) {
                            $newPs[$currency][$key] = $methods;
                        } elseif (in_array($currency, $field['currencies'])) {
                            $newPs[$currency][$key] = $methods;
                        }
                    }
                }
            }
        }

        return $newPs;
    }

    /**
     * @param $ps
     * @return array
     */
    public static function secondCategory(array $ps): array
    {
        $currencyKey = [];
        $i = 0;
        foreach ($ps as $key => $val) {
            if ($i == 0) {
                foreach ($val as $k => $v) {
                    $currencyKey[] = $k;
                }
            } else {
                foreach ($val as $method) {
                    foreach ($method as $field) {
                        if (isset($field['currencies'])) {
                            $currencyKey = array_merge($currencyKey, $field['currencies']);
                        }
                    }
                }
            }
            $i++;
        }

        $currencyKey = array_unique($currencyKey);

        $j = 0;
        $newPs = [];
        foreach ($currencyKey as $currency) {
            foreach ($ps as $key => $methods) {
                if ($j == 0) {
                    foreach ($methods as $k => $currencyType) {
                        foreach ($currencyType as $method) {
                            foreach ($method as $field) {

                                if (empty($field)) {
                                    $newPs[$currency][$key] = $methods;
                                } elseif (isset($field['currencies']) && in_array($currency, $field['currencies'])) {
                                    $newPs[$currency][$key] = $methods;
                                }
                            }
                        }
                    }

                } else {
                    foreach ($methods as $method) {
                        foreach ($method as $field) {
                            if (empty($field)) {
                                $newPs[$currency][$key] = $methods;
                            } elseif (isset($field['currencies']) && in_array($currency, $field['currencies'])) {
                                $newPs[$currency][$key] = $methods;
                            }
                        }
                    }
                }
                $j++;
            }
        }

        return $newPs;
    }

    /**
     * @param $ps
     * @param $accountMethods
     * @return array
     */
    public static function thirdCategory(array $ps, array $accountMethods): array
    {
        $currencyKey = [];
        foreach ($accountMethods as $key => $val) {
            $currencyKey[] = strtoupper($key);
        }
        $currencyKey = array_unique($currencyKey);

        $newPs = [];
        foreach ($currencyKey as $currency) {
            foreach ($ps as $key => $methods) {
                $newPs[$currency][$key] = $methods;
            }
        }

        return $newPs;
    }

    /**
     * @param $accountMethods
     * @return array
     */
    public static function forEmptyPs(array $accountMethods): array
    {
        $newPs = [];
        foreach ($accountMethods as $key => $val) {
            $newPs[strtoupper($key)] = $val;
        }
        return $newPs;
    }
}