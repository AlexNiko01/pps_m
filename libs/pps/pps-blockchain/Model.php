<?php

namespace pps\blockchain;

use pps\payment\IModel;
use yii\helpers\Html;

class Model extends \yii\base\Model implements IModel
{
    public $xpub;
    public $api_key;
    // For wallet API
    public $guid;
    public $password;
    public $second_password;
    public $address;
    public $auto_min_fee_per_byte;
    public $min_fee_per_byte;


    public function attributeLabels(): array
    {
        return [
            'xpub' => 'Xpub address',
            'api_key' => 'API key',
            'guid' => 'Wallet API ID',
            'password' => 'Wallet API password',
            'second_password' => 'Wallet API second password',
            //'address' => 'Wallet address',
        ];
    }

    public function rules(): array
    {
        return [
            [['xpub', 'api_key', 'guid', 'password'/*, 'address'*/], 'required'],
            [['xpub', 'api_key', 'guid', 'password', 'second_password'/*, 'address'*/], 'trim'],
            ['auto_min_fee_per_byte', 'boolean'],
            ['min_fee_per_byte', 'number', 'min' => 1, 'max' => 200],
            ['min_fee_per_byte', 'required', 'when' => function ($model) {
                return $model->autoFeePerByte;
            }, 'whenClient' => "function (attribute, value) {
                return $('#" . Html::getInputId($this, 'auto_min_fee_per_byte') . "').prop('checked');
            }"],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields(): array
    {
        return [
            'xpub' => 'text',
            'api_key' => 'text',
            'guid' => 'text',
            'password' => 'password',
            'second_password' => 'password',
            //'address' => 'text',
            'auto_min_fee_per_byte' => 'checkbox',
            'min_fee_per_byte' => 'number',
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
            'public_key' => 'xpub',
            'private_key' => 'api_key',
            'field3' => 'guid',
            'field4' => 'password',
            'field5' => 'second_password',
        ];

        return $keys[$key] ?? null;
    }
}