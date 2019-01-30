<?php

namespace backend\models;

use common\models\Transaction;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * Class PaymentSystem
 * @package backend\models
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $active
 * @property string $logo
 * @property string $callback_url
 * @property int $created_at
 * @property int $updated_at
 */
class PaymentSystem extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%payment_system}}';
    }

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return \Yii::$app->db2;
    }

    /**
     * @param string $code
     * @return int|null
     */
    public static function getIdByCode(string $code)
    {
        if (!$entity = self::findOne(['code' => $code])) {
            return null;
        };
        return $entity->id;
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => function () {
                    return date('U');
                }
            ]
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'code' => 'Payment system code',
            'active' => 'Is Active',
            'callback_url' => 'Callback URL'
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['code', 'unique'],
            ['code', 'string', 'max' => 16],
            ['logo', 'string', 'max' => 255],
            ['name', 'string', 'max' => 32],
            [['code', 'active', 'name'], 'required'],

            ['callback_url', 'string', 'max' => 255],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserPaymentSystem()
    {
        return $this->hasOne(UserPaymentSystem::className(), ['payment_system_id' => 'id']);
    }

    /**
     * @return array
     */
    public static function getCodes(): array
    {
        $paymentSystems = static::find()->asArray()->all();
        return ArrayHelper::map($paymentSystems, 'id', 'code');
    }
}