<?php

namespace pps\core;

use yii\base\{Component, Model, InvalidParamException};
use pps\payment\Payment;

/**
 * Class Core
 * @package pps\core
 */
class Core extends Component
{
    /**
     * An array that contains classes of connected payment systems
     * @var array;
     */
    public $payments;

    /**
     * Initialization PPS component
     * @throws InvalidParamException
     */
    public function init() {
        parent::init();

        if(!empty($this->payments)) {
            foreach ($this->payments as $key => $payment) {
                if(!class_exists($payment['class'])) {
                    unset($this->payments[$key]);
                }
            }
        }
    }

    /**
     * Return an instance of the class or the class to call static methods
     * @param string $type
     * @param array $contract
     * @param bool $static
     * @return Payment|string|bool
     */
    public function load(string $type, array $contract = [], bool $static = false)
    {
        if (!array_key_exists($type, $this->payments)) {
            return false;
        }

        $params = $this->payments[$type];
        $class = $params['class'];
        unset($params['class']);
        $contract = array_merge($contract, $params);

        return $static ? $class : new $class($contract);
    }

    /**
     * Method for getting the model class
     * @param string $type
     * @return Model|bool
     */
    public function model(string $type)
    {
        /**
         * @var Payment $class
         */
        if (!array_key_exists($type, $this->payments)) {
            return false;
        }
        $class = $this->payments[$type]['class'];
        return $class::getModel();
    }

    /**
     * Getting info all payment systems
     * @return array
     */
    public function getAll():array
    {
        $data = [];
        foreach ($this->payments as $key => $class) {
            $data[$key] = $this->getInfo($key);
        }
        return $data;
    }

    /**
     * Getting info one payment system
     * @param string $type
     * @return array|bool
     */
    public function getInfo(string $type)
    {
        /**
         * @var Payment $class
         */
        if(!array_key_exists($type, $this->payments)) return false;

        $class = $this->payments[$type]['class'];

        return [
            'currencies' => $class::getSupportedCurrencies()
        ];
    }

}