<?php

namespace pps\testps;

use pps\payment\IModel;
use pps\payment\MultiSettingsInterface;
use pps\payment\PaymentModel;

/**
 * Class Model
 * @package pps\testps
 */
class Model extends PaymentModel implements IModel, MultiSettingsInterface
{
    public $example_key;


    public function attributeLabels():array
    {
        return [
            'example_key' => 'Example key'
        ];
    }

    public function rules():array
    {
        return [
            ['example_key', 'required', 'on' => PaymentModel::SCENARIO_REQUIRED],
            ['example_key', 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'example_key' => 'text'
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
            'public_key' => 'example_key'
        ];
        return $keys[$key] ?? null;
    }

    /**
     * @return array
     */
    public function requiredApiKeys()
    {
        return ['public_key'];
    }

    /**
     * @return array
     */
    public function currencies(): array
    {
        return ['CUR', 'REN'];
    }
}
