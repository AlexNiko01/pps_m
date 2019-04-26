<?php

namespace backend\controllers;


use webvimark\components\BaseController;
use common\components\helpers\Hash;
use common\models\AuthLog;
use yii\filters\AccessControl;

class ErrorController extends BaseController
{

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['error'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['error'],
                        'roles' => ['@'],
                    ],
                ],
            ]
        ];
    }

    /**
     * @return string
     */
    public function actionError()
    {
        $currentIp = \Yii::$app->request->getUserIP();
        $currentUserAgent = \Yii::$app->request->getUserAgent();
        $currentHash = Hash::sha1($currentUserAgent);
        $authLog = AuthLog::find()->where(['ip' => $currentIp, 'user_agent' => $currentHash])->one();
        if (!$authLog || $authLog->unblocking_time < time()) {
            $this->redirect(['site/index']);
        }
        $unblockingTime = $authLog->unblocking_time ? date("Y/m/d  H:i:s", $authLog->unblocking_time) : '';
        $this->layout = 'ban';
        return $this->render('error',
            [
                'unblockingTime' => $unblockingTime,
                'interval' => ($authLog->unblocking_time - time())
            ]
        );
    }

}