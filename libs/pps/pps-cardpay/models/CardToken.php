<?php

namespace pps\cardpay\models;

use yii\db\ActiveRecord;

/**
 * @property int $rand_id
 * @property string $buyer_id
 * @property string $number
 * @property string $token
 * Class CardToken
 * @package pps\cardpay\models
 */
class CardToken extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'card_token';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['brand_id', 'buyer_id', 'token'], 'required'],
            ['buyer_id', 'string', 'max' => 32],
            ['number', 'string', 'max' => 20],
            ['token', 'string', 'max' => 64],
        ];
    }

    /**
     * @param int $brand_id
     * @param int|string $buyer_id
     * @param string $number
     * @param string $token
     * @return bool
     */
    public static function saveToken($brand_id, $buyer_id, $number, $token)
    {
        if (empty($buyer_id)) {
            return false;
        }

        $cardToken = self::getToken($brand_id, $buyer_id, $number) ?? new self();

        if ($cardToken->isNewRecord) {
            $cardToken->brand_id = $brand_id;
            $cardToken->number = $number;
            $cardToken->buyer_id = $buyer_id;
            $cardToken->token = $token;

            return $cardToken->save();
        } else {
            if ($cardToken->token != $token) {
                $cardToken->token = $token;
                return $cardToken->save();
            }

            return true;
        }
    }

    /**
     * @param int $brand_id
     * @param int|string $buyer_id
     * @param string $number
     * @return CardToken
     */
    public static function getToken($brand_id, $buyer_id, $number)
    {
        return self::findOne([
            'brand_id' => $brand_id,
            'buyer_id' => $buyer_id,
            'number' => $number,
        ]);
    }

    /**
     * @param int $number
     * @return string
     */
    public static function hideCard($number)
    {
        return substr($number, 0, 6) . '...' . substr($number, -4);
    }
}