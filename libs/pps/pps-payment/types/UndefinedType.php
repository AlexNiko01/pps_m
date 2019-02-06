<?php

namespace pps\Payment\types;

/**
 * Class UndefinedType
 * @package pps\Payment\types
 */
class UndefinedType extends BaseType
{
    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'undefined';
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return [];
    }
}