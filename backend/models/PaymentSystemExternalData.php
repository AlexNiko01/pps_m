<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * Class PaymentSystemExternalData
 * @package backend\models
 * @property int $id
 * @property int $brand_id
 * @property int $payment_system_id
 * @property string|null $attr_code
 * @property string|null $value
 * @property string|null $currency
 */
class PaymentSystemExternalData extends ActiveRecord
{
    /**
     * @return array
     */
    public static function primaryKey()
    {
        return ['brand_id', 'payment_system_id', 'attr_code', 'currency'];
    }

    public function rules()
    {
        return [
            [['brand_id', 'payment_system_id', 'attr_code'], 'required'],
            [['brand_id', 'payment_system_id'], 'integer'],
            ['attr_code', 'string', 'max' => 36],
            ['value', 'trim'],
            ['currency', 'string', 'max' => 10],
        ];
    }

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return \Yii::$app->db2;
    }

}
