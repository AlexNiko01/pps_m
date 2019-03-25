<?php

namespace common\components\sender;


interface Sender
{
    /**
     * @param string $message
     * @return mixed
     */
    public function send(string $message);
}