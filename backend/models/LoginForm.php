<?php

namespace backend\models;


class LoginForm extends \webvimark\modules\UserManagement\models\forms\LoginForm
{
    public $captcha;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['captcha', 'captcha', 'captchaAction' => '/auth/captcha'];
        return $rules;

    }
}