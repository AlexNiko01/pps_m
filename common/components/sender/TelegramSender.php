<?php

namespace common\components\sender;

use backend\models\Settings;

class TelegramSender implements Sender
{

    /**
     * @param array $messageArr
     * @return mixed|void
     */
    //        TODO:  in catch remove "\Yii::$app->sender->send($e->getMessage())". return false if not \Yii::$app->telegram

    public function send($messageArr)
    {
        /**
         * @var \Yii::$app->telegram aki\telegram\Telegram
         */
        try {
            $chatId = Settings::getValue('chat_id');
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