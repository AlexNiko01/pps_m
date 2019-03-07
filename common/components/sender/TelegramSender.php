<?php

namespace common\components\sender;

use backend\models\Settings;

class TelegramSender implements Sender
{

    /**
     * @param array $messageArr
     * @return mixed|void
     */
    public function send($messageArr)
    {
        /**
         * @var \Yii::$app->telegram aki\telegram\Telegram
         */
        $chatIdSample = Settings::find()->where(['key' => 'chatId'])->select('value')->asArray()->one();

        $message = '';
        foreach ($messageArr as $item) {
            $message .= '<pre>' . $item . '</pre>' . PHP_EOL;
        }

        \Yii::$app->telegram->sendMessage([
            'chat_id' => $chatIdSample['value'],
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);

    }
}