<?php

namespace pps\payment\types;

/**
 * Class YamoneyType
 * @package pps\payment\types
 */
class YamoneyType extends BaseType
{
    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'yamoney';
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return [
            'purse' => $this->getPurse(),
        ];
    }

    /**
     * @return mixed|null
     */
    public function getPurse()
    {
        return $this->_getField('purse');
    }
}