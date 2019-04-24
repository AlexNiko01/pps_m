<?php

namespace backend\controllers;

use backend\models\LoginForm;
use common\components\helpers\Hash;
use webvimark\modules\UserManagement\controllers\AuthController;
use Yii;
use yii\web\Response;
use yii\widgets\ActiveForm;
use common\models\AuthLog;

class UserAuthController extends AuthController
{
    /**
     * @var array
     */
    public $freeAccessActions = ['login', 'logout', 'confirm-registration-email'];

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'minLength' => 3,
                'maxLength' => 4,
                'offset' => 5
            ],
        ];
    }

    private function showCaptcha(): bool
    {
        $showCaptcha = false;
        $currentIp = \Yii::$app->request->getUserIP();
        $currentUserAgent = \Yii::$app->request->getUserAgent();
        $currentHash = Hash::sha1($currentUserAgent);
        $authLog = AuthLog::find()->where(['ip' => $currentIp, 'user_agent' => $currentHash])->one();
        if ($authLog && $authLog->attempts >= 1) {
            $showCaptcha = true;
        }
        return $showCaptcha;
    }

    /**
     * Login form
     *
     * @return string
     */
    public function actionLogin()
    {
        \Yii::$app->cache->flush();
        $this->layout = 'auth';
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $showCaptcha = $this->showCaptcha();
        $model = new LoginForm(['scenario'=>LoginForm::SCENARIO_LOGIN_DEFAULT]);
        if($showCaptcha){
            $model = new LoginForm(['scenario'=>LoginForm::SCENARIO_LOGIN_VERIFICATION]);
        }

        if (Yii::$app->request->isAjax AND $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return ActiveForm::validate($model);
        }
        if ($model->load(Yii::$app->request->post()) AND $model->login()) {
            return $this->goBack();
        }


        return $this->render('login',
            [
                'model' => $model,
                'showCaptcha' => $showCaptcha
            ]);
    }


}
