<?php

namespace backend\controllers;


use webvimark\modules\UserManagement\controllers\AuthController;
use webvimark\modules\UserManagement\models\forms\LoginForm;
use yii\web\Response;
use yii\widgets\ActiveForm;

class LoginController extends AuthController
{
    /**
     * Login form
     *
     * @return string
     */
    public function actionLogin()
    {
        if ( !\Yii::$app->user->isGuest )
        {
            return $this->goHome();
        }

        $model = new LoginForm();

        if ( \Yii::$app->request->isAjax AND $model->load(\Yii::$app->request->post()) )
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ( $model->load(\Yii::$app->request->post()) AND $model->login() )
        {
            return $this->goBack();
        }

        return $this->renderIsAjax('login', compact('model'));
    }

}