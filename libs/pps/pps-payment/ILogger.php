<?php

namespace pps\payment;

/**
 * Interface ILogger
 * @package pps\payment
 */
interface ILogger
{
    /**
     * @param mixed $txid
     * @param int $type
     * @param mixed $data
     * @return bool
     */
    public function log($txid, int $type, $data);
}