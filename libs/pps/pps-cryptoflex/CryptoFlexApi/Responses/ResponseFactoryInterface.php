<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 14.08.18
 * Time: 12:05
 */

namespace pps\cryptoflex\CryptoFlexApi\Responses;

use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;

interface ResponseFactoryInterface
{
    public static function getResponse(CryptoFlexConfig $apiConfig, array $params = []): CryptoFlexResponseInterface;
}
