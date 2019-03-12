<?php

namespace common\components\sender;

use backend\models\Settings;
use common\components\exception\SettingsException;

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
        if (!\Yii::$app->telegram) {
            return null;
        }
        try {
            $chatId = Settings::getValue('chat_id');
        } catch (SettingsException $e) {
            \Yii::info($e->getMessage());
            return null;
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