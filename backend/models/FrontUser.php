<?php

namespace backend\models;

use webvimark\modules\UserManagement\models\User;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * Class FrontUser
 * @package backend\models
 */
class FrontUser extends User
{
    /**
     * Used in form to assign role to user immediately
     * @var string
     */
    public $role_name;


    public function rules()
    {
        return [
            ['role_name', 'required', 'except' => 'changePassword'],

            ['username', 'required'],
            ['username', 'unique'],
            ['username', 'trim'],

            [['status', 'email_confirmed'], 'integer'],

            ['email', 'email'],
            ['email', 'validateEmailConfirmedUnique'],

            ['bind_to_ip', 'validateBindToIp'],
            ['bind_to_ip', 'trim'],
            ['bind_to_ip', 'string', 'max' => 255],

            ['password', 'required', 'on' => ['newUser', 'changePassword']],
            ['password', 'string', 'max' => 255, 'on' => ['newUser', 'changePassword']],
            ['password', 'trim', 'on' => ['newUser', 'changePassword']],

            ['repeat_password', 'required', 'on' => ['newUser', 'changePassword']],
            ['repeat_password', 'compare', 'compareAttribute' => 'password'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'role_name' => Yii::t('admin', 'Role'),
        ]);
    }
}