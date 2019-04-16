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
    public function bootstrap($app)
    {
        $events = [Model::EVENT_BEFORE_VALIDATE, User::EVENT_AFTER_LOGIN];

        foreach ($events as $eventName) {
            Event::on(Model::class, $eventName, function ($event) {
//                var_dump($event->sender);
                if ($event->sender instanceof LoginForm) {
                    $loginForm = $event->sender;
//                    var_dump($loginForm);
//                    var_dump(\Yii::$app->request->getUserIP());
                }
            });
            Event::on(User::class, $eventName, function ($event) {
//                var_dump($event->sender);
                if ($event->sender instanceof UserConfig) {
                    $user = $event->sender;
//                    var_dump($user);
//                    var_dump(\Yii::$app->request->getUserIP());
                }

            });
        }
    }

}