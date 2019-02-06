<?php

namespace pps\payment\types;

/**
 * Class Privat24Type
 * @package pps\payment\types
 */
class Privat24Type extends BaseType
{
    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'privat24';
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return [
            'number' => $this->getNumber(),
            'holder' => $this->getHolder(),
        ];
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->_getField('number');
    }

    /**
     * @return mixed
     */
    public function getHolder()
    {
        return $this->_getField('holder');
    }
}