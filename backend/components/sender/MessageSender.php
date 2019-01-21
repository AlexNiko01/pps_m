<?php

namespace backend\components\sender;

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
     * @param string $message
     */
    public function send(string $message)
    {
        foreach ($this->senders as $sender) {
            $sender->send($message);
        }
    }
}