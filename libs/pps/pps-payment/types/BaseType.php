<?php

namespace pps\payment\types;

/**
 * Class BaseType
 * @package pps\payment\types
 */
abstract class BaseType
{
    /** @var array */
    protected $_fields = [];
    /** @var array */
    protected $_requisites = [];


    /**
     * $fields = ['ps_payer_account' => 'number']
     * CardType constructor.
     * @param array $fields
     * @param array $requisites
     */
    public function __construct(array $fields, array $requisites)
    {
        $this->_fields = array_flip($fields);
        $this->_requisites = $requisites;
    }

    /**
     * Load data to model
     * @param array $requisites
     * @return static
     */
    public static function load(array $requisites)
    {
        $keys = array_keys($requisites);
        $fields = array_combine($keys, $keys);

        return new static($fields, $requisites);
    }

    /**
     * Get all fields
     * @return array
     */
    public function getFieldsWithUndefined():array
    {
        $fields = $this->getFields();
        $requisites = $this->_requisites;

        foreach ($fields as $k => $v) {
            if (isset($this->_fields[$k]) && isset($requisites[$this->_fields[$k]])) unset($requisites[$this->_fields[$k]]);
        }

        foreach (array_values($requisites) as $k => $requisite) {
            $fields["undefined_" . ($k + 1)] = $requisite;
        }

        return $fields;
    }

    /**
     * Get filed from requisites
     * @param string|int $key
     * @return mixed|null
     */
    protected function _getField($key)
    {
        if (isset($this->_fields[$key]) && isset($this->_requisites[$this->_fields[$key]])) {
            return $this->_requisites[$this->_fields[$key]];
        }

        return null;
    }

    /**
     * @return string
     */
    abstract public static function getType() :string;

    /**
     * Get fields
     * @return array
     */
    abstract public function getFields();
}