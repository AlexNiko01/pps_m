<?php

namespace pps\payment;

interface IModel
{
    const FIELD_TYPE_TEXT = 'text';
    const FIELD_TYPE_NUMBER = 'number';
    const FIELD_TYPE_EMAIL = 'email';
    const FIELD_TYPE_PASSWORD = 'password';
    const FIELD_TYPE_READONLY = 'readonly';
    const FIELD_TYPE_CHECKBOX = 'checkbox';

    /**
     * Method should to return associative array where key is field and value is type (number, text)
     * ['text', 'email', 'password', 'readonly']
     * @return array
     */
    public function getFields():array;

    /**
     * Transformation key from inside API to key from payment system
     * @param string $key
     * @return string|int|null
     */
    public function transformApiKey(string $key);
}
