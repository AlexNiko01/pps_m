<?php

namespace backend\components\sender;

use yii\db\ActiveRecord;

class MessageSender extends \yii\base\Component
{
    /**
     * @var array
     */
    public $senders;

    /**
     * @param Sender $sender
     * @return $this
     */
    public function addSender(Sender $sender)
    {
        $this->senders[] = $sender;
        return $this;
    }

    /**
     * @param array $messageArr
     */
    public function send(array $messageArr)
    {
        foreach ($this->senders as $sender) {
            $sender->send($messageArr);
        }
    }
}