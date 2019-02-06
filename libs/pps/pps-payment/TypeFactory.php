<?php

namespace pps\payment;

/**
 * Class TypeFactory
 * @package pps\payment
 */
class TypeFactory
{
    /** @var array */
    protected $_requisites = [];
    /** @var array */
    protected static $_aliases = [
        'card' => types\CardType::class,
        'privat24' => types\Privat24Type::class,
        'qiwi' => types\QiwiType::class,
        'yamoney' => types\YamoneyType::class,
    ];


    /**
     * @param string $type
     * @param $class
     */
    public static function addAlias(string $type, $class)
    {
        self::$_aliases[$type] = $class;
    }

    /**
     * TypeFactory constructor.
     * @param array $requisites
     */
    public function __construct(array $requisites)
    {
        $this->_requisites = $requisites;
    }

    /**
     * @param string $type
     * @param array $fields
     * @return types\BaseType
     */
    public function getInstance(string $type, array $fields)
    {
        if (in_array($type, array_keys(self::$_aliases))) {
            return new self::$_aliases[$type]($fields, $this->_requisites);
        } else {
            return new types\UndefinedType($fields, $this->_requisites);
        }
    }
}