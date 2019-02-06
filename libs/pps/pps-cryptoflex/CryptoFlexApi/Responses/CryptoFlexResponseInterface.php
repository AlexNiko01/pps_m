<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 14:39
 */

namespace pps\cryptoflex\CryptoFlexApi\Responses;

interface CryptoFlexResponseInterface
{
    public function getResponseBody(): array;
}

