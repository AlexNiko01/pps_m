<?php

namespace backend\components\sender;


use backend\models\Settings;

class RocketChatSender implements Sender
{
    /**
     * @param array $messageArr
     * @return mixed|void
     */
    public function send($messageArr)
    {
        $url = Settings::find()->where(['key' => 'rocket_chat_url'])->select('value')->asArray()->one()['value'];
        define('REST_API_ROOT', '/api/v1/');
        define('ROCKET_CHAT_INSTANCE', $url);
        new \RocketChat\Client();

        $userName = Settings::find()->where(['key' => 'rocket_chat_user'])->select('value')->asArray()->one()['value'];
        $password = Settings::find()->where(['key' => 'rocket_chat_password'])->select('value')->asArray()->one()['value'];
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