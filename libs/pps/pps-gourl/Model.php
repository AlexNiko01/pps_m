<?php

namespace pps\gourl;

use pps\payment\IModel;
use pps\payment\MultiSettingsInterface;
use pps\payment\PaymentModel;

class Model extends PaymentModel implements IModel, MultiSettingsInterface
{
    public $public_key;
    public $private_key;
    public $box_id;
    public $webdev_key;


    public function attributeLabels(): array
    {
        return [
            'public_key' => 'Public key',
            'private_key' => 'Private key',
            'box_id' => 'Box ID',
            'webdev_key' => 'Web Dev Key',
        ];
    }

    public function rules(): array
    {
        return [
            [['public_key', 'private_key', 'box_id'], 'required', 'on' => PaymentModel::SCENARIO_REQUIRED],
            [['public_key', 'private_key', 'webdev_key', 'box_id'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields(): array
    {
        return [
            'public_key' => 'text',
            'private_key' => 'text',
            'box_id' => 'number',
            'webdev_key' => 'text',
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
            'public_key' => 'public_key',
            'private_key' => 'private_key',
            'field3' => 'box_id',
            'field4' => 'webdev_key',
        ];

        return $keys[$key] ?? null;
    }

    /**
     * @return array
     */
    public function currencies(): array
    {
        return ['BTC', 'BCC', 'LTC', 'ECN', 'SPD', 'DASH', 'DOGE', 'RDD', 'POT', 'FTC', 'VTS', 'PPC', 'MUE'];
    }
}