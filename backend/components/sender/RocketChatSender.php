<?php

namespace backend\components\sender;


class RocketChatSender implements Sender
{
    /**
     * @param $message
     * @return mixed|void
     */
    public function send($message)
    {
        define('REST_API_ROOT', '/api/v1/');
        define('ROCKET_CHAT_INSTANCE', 'https://pop888.pw');
        new \RocketChat\Client();

        $user = new \RocketChat\User('o.semenchuk', 'enotpoloskun');
        if (!$user->login(true)) {
            $user->create();
        }

        $channel = new \RocketChat\Channel('pps_monitoring', array($user));
        $channel->postMessage('Hello world2');
    }
}