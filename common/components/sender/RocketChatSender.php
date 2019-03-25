<?php

namespace common\components\sender;


use backend\models\Settings;
use common\components\exception\SettingsException;

class RocketChatSender implements Sender
{

    /**
     * @param string $message
     * @return mixed|void
     */
    public function send(string $message)
    {
        try {
            $url = Settings::getValue('rocket_chat_url');
            $userName = Settings::getValue('rocket_chat_user');
            $password = Settings::getValue('rocket_chat_password');
        } catch (SettingsException  $e) {
            \Yii::info($e->getMessage(), 'settings');
            return null;
        }

        define('REST_API_ROOT', '/api/v1/');
        define('ROCKET_CHAT_INSTANCE', $url);

        $user = new \RocketChat\User($userName, $password);
        if (!$user->login(true)) {
            $user->create();
        }

        $channel = new \RocketChat\Channel('pps_monitoring', array($user));

        if (!$message) {
            return null;
        }
        $channel->postMessage($message);
    }


}