<?php

namespace common\components\sender;


use backend\models\Settings;

class RocketChatSender implements Sender
{
    /**
     * @param array $messageArr
     * @return mixed|void
     */
    public function send($messageArr)
    {
        $url = Settings::getValue('rocket_chat_url');
        define('REST_API_ROOT', '/api/v1/');
        define('ROCKET_CHAT_INSTANCE', $url);
        new \RocketChat\Client();

        $userName = Settings::getValue('rocket_chat_user');
        $password = Settings::getValue('rocket_chat_password');
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