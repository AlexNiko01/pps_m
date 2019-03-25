<?php

namespace common\components\sender;

use backend\models\Settings;
use common\components\exception\SettingsException;

class TelegramSender implements Sender
{

    /**
     * @param string $message
     * @return mixed|void
     */

    public function send(string $message)
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
            \Yii::info($e->getMessage(), 'settings');
            return null;
        }

        if (!$message) {
            return null;
        }
        \Yii::$app->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);
    }
}