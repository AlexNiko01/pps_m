<?php

namespace common\components\bootstrap;

use webvimark\modules\UserManagement\components\UserConfig;
use webvimark\modules\UserManagement\models\forms\LoginForm;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\base\Model;

use yii\web\User;

class EventBootstrap implements BootstrapInterface
{

    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app): void
    {
        $authLogService = \Yii::$app->authLogService;
        $authLogService->checkUserAccessibility();
        Event::on(LoginForm::class, Model::EVENT_BEFORE_VALIDATE, function ($event) use ($authLogService) {
            $authLogService->addAuthLog();
        });
        Event::on(UserConfig::class, User::EVENT_AFTER_LOGIN, function ($event) use ($authLogService) {
            $authLogService->removeAuthLog();
        });
    }

}