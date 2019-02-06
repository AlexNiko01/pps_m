<?php

namespace pps\payment\types;

/**
 * Class QiwiType
 * @package pps\payment\types
 */
class QiwiType extends BaseType
{
    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'qiwi';
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return [
            'name' => $this->getName(),
            'phone' => $this->getPhone(),
            'comment' => $this->getComment(),
        ];
    }

    /**
     * @return mixed|null
     */
    public function getName()
    {
        return $this->_getField('name');
    }

    /**
     * @return mixed|null
     */
    public function getComment()
    {
        return $this->_getField('comment');
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        $phone = $this->_getField('phone');

        if (!empty($phone)) {
            return ltrim($phone, '+');
        }

        return $phone;
    }
}