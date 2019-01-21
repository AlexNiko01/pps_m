<?php

namespace backend\components\sender;


use PhpRocketChatWebhooks\Client;

class RocketChatSender implements Sender
{
    /**
     * @param $message
     * @return mixed|void
     */
    public function send($message)
    {
        $rocketChatClient = new Client('https://pop888.pw/api/v1/chat.postMessage/a3TCkLaXZLRJOxaklCZ8-2pyxTpTA6Pc-LqNKlBb68y', 'o.semenchuk');
        $rocketChatClient->sendRequest($message);
    }
}