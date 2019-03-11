<?php

namespace common\components\sender;


use backend\models\Settings;

class RocketChatSender implements Sender
{

    /**
     * @param $messageArr
     * @return mixed|void
     */
    public function send($messageArr)
    {
        try {
            $url = Settings::getValue('rocket_chat_url');
        } catch (\SettingsException  $e) {
            \Yii::$app->sender->send($e->getMessage());
        }

        define('REST_API_ROOT', '/api/v1/');
        define('ROCKET_CHAT_INSTANCE', $url);

        try {
            $userName = Settings::getValue('rocket_chat_user');
        } catch (\SettingsException  $e) {
            \Yii::$app->sender->send($e->getMessage());
        }

        try {
            $password = Settings::getValue('rocket_chat_password');
        } catch (\SettingsException  $e) {
            \Yii::$app->sender->send($e->getMessage());
        }

        $user = new \RocketChat\User($userName, $password);
        if (!$user->login(true)) {
            $user->create();
        }

        $channel = new \RocketChat\Channel('pps_monitoring', array($user));

        $message = '';
        foreach ($messageArr as $item) {
            $message .= $item . PHP_EOL;
        }
        $channel->postMessage($message);
    }
}