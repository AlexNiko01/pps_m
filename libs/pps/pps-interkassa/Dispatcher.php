<?php

namespace pps\interkassa;

/**
 * Class Dispatcher
 * @package pps\interkassa
 */
class Dispatcher
{
    /** @var array */
    protected $currencies = [];
    /** @var array */
    protected $keys_map = [];
    /** @var array */
    protected $converted_currencies = [];
    /** @var array */
    protected $groups = [];
    /** @var string */
    protected $methodPattern = "~\\-[0-9]+$~";

    // Keys for caching
    public static $converted_currencies_key = ['Dispatcher', 'converted_currencies'];
    public static $keys_map_key = ['Dispatcher', 'keys_map'];
    public static $groups_key = ['Dispatcher', 'groups'];

    /**
     * Dispatcher constructor.
     * @param $currencies
     */
    public function __construct(array $currencies)
    {
        $this->currencies = $currencies;
        $this->convertKeys();
    }

    /**
     * @param string $currency
     * @param string $method
     * @param string $key
     * @return string|null
     */
    protected function getChangedKey(string $currency, string $method, string $key)
    {
        $assoc = [
            'RUB' => [
                'qiwi' => [
                    'user' => 'phone'
                ],
                'yamoney' => [
                    'recipient' => 'user'
                ],
                'webmoney' => [
                    'wallet' => 'purse',
                ],
                'visa' => [
                    'user' => 'card',
                    'client' => 'card',
                    'pan' => 'card',
                ],
                'mastercard' => [
                    'user' => 'card',
                    'client' => 'card',
                    'pan' => 'card',
                ],
                'tele2' => [
                    'user' => 'phone'
                ],
                'mts' => [
                    'user' => 'phone'
                ],
                'beeline' => [
                    'user' => 'phone'
                ],
                'mir' => [
                    'user' => 'card',
                    'pan' => 'card',
                ]
            ]
        ];

        return $assoc[$currency][$method][$key] ?? null;
    }

    protected function convertKeys()
    {
        foreach ($this->currencies as $currency => $methods) {
            foreach ($methods as $method => $data) {
                $cleanMethod = preg_replace($this->methodPattern, '', $method);
                foreach ($data['fields']['withdraw'] ?? [] as $key => $field) {

                    $newKey = $this->getChangedKey($currency, $cleanMethod, $key);

                    if ($newKey) {
                        $methods[$method]['fields']['withdraw'][$newKey] = $field;
                        unset($methods[$method]['fields']['withdraw'][$key]);
                        $this->keys_map[$currency][$method][$key] = $newKey;
                    }
                }
            }

            $this->converted_currencies[$currency] = $methods;
        }

        \Yii::$app->cache->set(self::$keys_map_key, $this->keys_map);
    }

    /**
     * @return array
     */
    public function getConvertedCurrencies()
    {
        if (empty($this->converted_currencies) && \Yii::$app->cache->exists(self::$converted_currencies_key)) {
            return \Yii::$app->cache->get(self::$converted_currencies_key);
        }
        $this->replaceMethods();

        return $this->converted_currencies;
    }

    /**
     * @return array
     */
    public function getKeysMap()
    {
        if (empty($this->keys_map) && \Yii::$app->cache->exists(self::$keys_map_key)) {
            return \Yii::$app->cache->get(self::$keys_map_key);
        }

        return $this->keys_map;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        if (empty($this->groups) && \Yii::$app->cache->exists(self::$groups_key)) {
            return \Yii::$app->cache->get(self::$groups_key);
        }

        return $this->groups;
    }

    /**
     * @return array
     */
    public function findMethodGroups()
    {
        foreach ($this->converted_currencies as $currency => $methods) {
            $this->groupMethods($currency);
        }

        \Yii::$app->cache->set(self::$groups_key, $this->groups);

        return $this->groups;
    }

    /**
     * @param $currency
     */
    protected function groupMethods(string $currency)
    {
        $methods = $this->converted_currencies[$currency];

        foreach ($methods as $method => $data) {
            if (isset($data['withdraw']) && $data['withdraw']) {
                $cleanMethod = preg_replace($this->methodPattern, '', $method);

                if (isset($this->groups[$currency][$cleanMethod])) {
                    $groupsOfMethod = $this->groups[$currency][$cleanMethod];
                    $new = true;

                    foreach ($groupsOfMethod as $i => $group) {
                        if ($this->isSameArrays(array_keys($data['fields']['withdraw']), $group['fields'])) {
                            $this->groups[$currency][$cleanMethod][$i]['keys'][] = $method;
                            $new = false;
                        }
                    }

                    if ($new) {
                        $this->groups[$currency][$cleanMethod][] = [
                            'fields' => array_keys($data['fields']['withdraw']),
                            'keys' => [$method],
                            'template' => $data
                        ];
                    }

                } else {
                    $this->groups[$currency][$cleanMethod][] = [
                        'fields' => array_keys($data['fields']['withdraw']),
                        'keys' => [$method],
                        'template' => $data
                    ];
                }
            }
        }
    }

    /**
     * @param $ar1
     * @param $ar2
     * @return bool
     */
    protected function isSameArrays(array $ar1, array $ar2)
    {
        return array_diff($ar1, $ar2) == array_diff($ar2, $ar1) && count($ar1) == count($ar2);
    }

    public function replaceMethods()
    {
        $this->findMethodGroups();

        foreach ($this->converted_currencies as $currency => $methods) {
            foreach ($methods as $method => $data) {
                if (isset($data['withdraw']) && $data['withdraw']) unset($this->converted_currencies[$currency][$method]);

                if (isset($data['deposit']) && $data['deposit']) {
                    preg_match("~\\-[0-9]+$~", $method, $matches);
                    if (isset($matches[0])) unset($this->converted_currencies[$currency][$method]);
                }
            }
            foreach ($this->groups[$currency] ?? [] as $method => $groups) {
                foreach ($groups as $i => $group) {
                    unset($group['template']['w_id']);
                    $paymentSystems = array_filter($this->converted_currencies[$currency], function ($item) use ($method) {
                        preg_match("~^($method)(-([0-9]+))?$~", $item, $matches);
                        return isset($matches[1]);
                        //return $item == $method;
                    }, ARRAY_FILTER_USE_KEY);

                    $number = count($paymentSystems) + 1;
                    $methodCode = $method . ($number > 1 ? "-{$number}" : '');

                    $group['template']['name'] = $methodCode;
                    $this->converted_currencies[$currency][$methodCode] = $group['template'];
                    $this->converted_currencies[$currency][$methodCode]['group_index'] = $i;
                }
            }
        }

        \Yii::$app->cache->set(self::$converted_currencies_key, $this->converted_currencies);
    }

    /**
     * @param $currency
     * @param $method
     * @param $userCurrencies
     * @return null|string
     */
    public function getWithdrawId(string $currency, string $method, array $userCurrencies)
    {
        $methodOrigin = $this->findWithdrawOriginMethod($currency, $method, $userCurrencies);

        if (!$methodOrigin) return $methodOrigin;

        return ($methodOrigin && isset($this->currencies[$currency][$methodOrigin]['w_id'])) ?
            $this->currencies[$currency][$methodOrigin]['w_id'] :
            null;
    }

    /**
     * @param $currency
     * @param $method
     * @param $userCurrencies
     * @return null|string
     */
    public function findWithdrawOriginMethod(string $currency, string $method, array $userCurrencies)
    {
        $groups = $this->getGroups();
        $converted_currencies = $this->getConvertedCurrencies();
        if (!isset($groups[$currency])) return null;

        $groupIndex = isset($converted_currencies[$currency][$method]['group_index']) ? $converted_currencies[$currency][$method]['group_index'] : null;

        if ($groupIndex === null) return null;

        $cleanMethod = preg_replace($this->methodPattern, '', $method);

        $keys = $groups[$currency][$cleanMethod][$groupIndex]['keys'] ?? null;

        if (!$keys) return null;

        //return [$userCurrencies, $keys];

        foreach ($userCurrencies as $userCurrency) {
            list($curr, $met, $way) = explode('_', $userCurrency);
            $curr = strtoupper($curr);
            if ($way == 'withdraw' && $currency == $curr && in_array($met, $keys)) {
                return $met;
            }
        }

        return false;
    }

    /**
     * @param $currency
     * @param $inputMethod
     * @param $userCurrencies
     * @return null|string
     * @internal param $method
     */
    public function getDepositId($currency, $inputMethod, $userCurrencies)
    {
        $cleanInputMethod = preg_replace($this->methodPattern, '', $inputMethod);

        foreach ($userCurrencies as $userCurrency) {
            list($curr, $method, $way) = explode('_', $userCurrency);
            $curr = strtoupper($curr);
            $cleanUserMethod = preg_replace($this->methodPattern, '', $method);
            if ($way == 'deposit' && $currency == $curr && $cleanUserMethod == $cleanInputMethod) {
                return $this->currencies[$currency][$method]['d_id'] ?? null;
            }
        }

        return false;
    }

    /**
     * @param $currency
     * @param $method
     * @param $userCurrencies
     * @return null|array
     */
    public function getKeysForReplacement($currency, $method, $userCurrencies)
    {
        $originMethod = $this->findWithdrawOriginMethod($currency, $method, $userCurrencies);
        $keys_map = $this->getKeysMap();

        return $keys_map[$currency][$originMethod] ?? null;
    }
}