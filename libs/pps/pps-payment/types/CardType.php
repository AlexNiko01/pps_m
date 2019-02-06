<?php

namespace pps\Payment\types;

/**
 * Class CardType
 * @package pps\Payment\types
 */
class CardType extends BaseType
{
    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'card';
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