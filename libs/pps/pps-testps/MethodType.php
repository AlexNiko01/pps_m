<?php
/**
 * Created by PhpStorm.
 * User: superuser
 * Date: 20.03.18
 * Time: 12:44
 */

namespace pps\testps;

use pps\payment\types\BaseType;

class MethodType extends BaseType
{
    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'method';
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return [
            'name' => $this->getName(),
            'email' => $this->getEmail(),
        ];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return strtoupper($this->_getField('name'));
    }

    /**
     * @return mixed|null
     */
    public function getEmail()
    {
        $email = $this->_getField('email');

        if (!empty($email)) {
            $email = str_replace('@', '[AT]', $email);
        }

        return $email;
    }
}