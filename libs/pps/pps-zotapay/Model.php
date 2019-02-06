<?php

namespace pps\zotapay;

use pps\payment\CommonInMultiSettingsInterface;
use pps\payment\IModel;
use pps\payment\PaymentModel;
use pps\zotapay\ZotaPayApi\Currencies\bank\BankCurrencies;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii\helpers\ArrayHelper;

class Model extends PaymentModel implements IModel, CommonInMultiSettingsInterface
{
    public $controlKey;
    public $endpointId;
    public $login;

    public function attributeLabels():array
    {
        return [
            'controlKey' => 'Control Key',
            'endpointId' => 'Endpoint Id',
            'login' => 'Login',
        ];
    }

    public function rules():array
    {
        return [
            [['controlKey', 'login'], 'required', 'on' => self::COMMON_PARAMS_SCENARIO],
            [['controlKey', 'login'], 'trim', 'on' => self::COMMON_PARAMS_SCENARIO],
            ['endpointId', 'trim', 'on' => self::INDIVIDUAL_PARAMS_SCENARIO],
            ['endpointId', 'required', 'on' => self::SCENARIO_REQUIRED],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'controlKey' => 'text',
            'endpointId' => 'text',
            'login' => 'text',
        ];
    }

    /**
     * Transformation key from inside API to key from payment system
     * @param string $key
     * @return string|int|null
     */
    public function transformApiKey(string $key)
    {
        $keys = [
            'public_key' => 'endpointId',
            'private_key' => 'controlKey',
            'field3' => 'login',
        ];
        return $keys[$key] ?? null;
    }

    public function attributes()
    {
        switch ($this->scenario) {
            case self::COMMON_PARAMS_SCENARIO:
                return $this->commonSettings();
            default:
                return array_diff(parent::attributes(), $this->commonSettings());
        }
    }

    /**
     * @return array
     */
    public function getRequiredParams(): array
    {
        return ['login', 'controlKey'];
    }

    /**
     * Array with list of settings which is common for all currencies
     * @return array
     */
    public function commonSettings(): array
    {
        return ['controlKey', 'login'];
    }

    /**
     * Array with list of currencies which has own settings
     * @return array
     */
    public function currencies(): array
    {
        $currencies = [];
        try {
            $currencies = \Yii::$container->get(BankCurrencies::class)->getCurrenciesList();
        } catch (NotInstantiableException $e) {
            \Yii::error($e, 'pps-zotapay');
        } catch (InvalidConfigException $e) {
            \Yii::error($e, 'pps-zotapay');
        }
        return ArrayHelper::merge([self::COMMON_NAME], $currencies);
    }
}
