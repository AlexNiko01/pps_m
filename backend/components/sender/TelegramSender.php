<?php

namespace backend\components\sender;

class TelegramSender implements Sender
{
    const CHAT_ID = -371816849;

    /**
     * @param $message
     * @return mixed|void
     */
    public function send($message)
    {
        /**
         * @var \Yii::$app->telegram aki\telegram\Telegram
         */
        \Yii::$app->telegram->sendMessage([
            'chat_id' => self::CHAT_ID,
            'text' => $message,
        ]);

    }
}