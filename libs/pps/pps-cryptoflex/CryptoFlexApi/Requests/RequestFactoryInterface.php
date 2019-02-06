<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 14.08.18
 * Time: 12:05
 */

namespace pps\cryptoflex\CryptoFlexApi\Requests;

use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\Requests\CryptoFlexRequestInterface;

interface RequestFactoryInterface
{
    public static function getRequest(CryptoFlexConfig $apiConfig, array $params = []): CryptoFlexRequestInterface;
}
