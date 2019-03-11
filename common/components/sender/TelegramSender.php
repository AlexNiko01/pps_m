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
        try {
            $chatId = Settings::getValue('chatId');
        } catch (\SettingsException $e) {
            \Yii::$app->sender->send($e->getMessage());
        }

        $message = '';
        foreach ($messageArr as $item) {
            $message .= '<pre>' . $item . '</pre>' . PHP_EOL;
        }

        \Yii::$app->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);
    }
}