<?php

namespace backend\components\sender;

use backend\models\Settings;

class TelegramSender implements Sender
{

    /**
     * @param $message
     * @return mixed|void
     */
    public function send($message)
    {
        /**
         * @var \Yii::$app->telegram aki\telegram\Telegram
         */
        $chatIdSample = Settings::find()->where(['key' => 'chatId'])->select('value')->asArray()->one();
        \Yii::$app->telegram->sendMessage([
            'chat_id' => $chatIdSample['value'],
            'text' => $message,
        ]);

    }
}