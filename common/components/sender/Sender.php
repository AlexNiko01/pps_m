<?php

namespace common\components\sender;


interface Sender
{
    /**
     * @param $message
     * @return mixed
     */
    public function send($message);
}