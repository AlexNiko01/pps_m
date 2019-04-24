<?php

namespace backend\models;


class LoginForm extends \webvimark\modules\UserManagement\models\forms\LoginForm
{
    const SCENARIO_LOGIN_DEFAULT = 'login_default';
    const SCENARIO_LOGIN_VERIFICATION = 'login_verification';


    public $captcha;

    /**
     * @return array
     */
    public function scenarios()
    {
        return [
            self::SCENARIO_LOGIN_DEFAULT => ['username', 'password'],
            self::SCENARIO_LOGIN_VERIFICATION => ['username', 'password', 'captcha'],
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['captcha', 'captcha', 'captchaAction' => '/user-auth/captcha'];
        return $rules;

    }
}