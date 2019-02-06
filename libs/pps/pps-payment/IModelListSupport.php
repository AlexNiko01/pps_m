<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 15.11.18
 * Time: 12:21
 */

namespace pps\payment;

interface IModelListSupport extends IModel
{
    const FIELD_TYPE_LIST = 'list';

    /**
     * Return the all possible values for list field
     * @param string $listFieldName
     * @return array
     */
    public function getListValues(string $listFieldName): array;
}