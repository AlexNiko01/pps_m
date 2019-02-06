<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 19.07.18
 * Time: 10:11
 */

namespace pps\cryptoflex\CryptoFlexApi\Requests;

use GuzzleHttp\Psr7\Request;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;

/**
 * @property  array $mandatoryFields
*/

interface CryptoFlexRequestInterface
{
    public function __toString();

    public function __construct(CryptoFlexConfig $config);

    public function getMethod(): string;

    /**
     * return URL of request to CryptoFlex
     * @return string
    */
    public function getRequestUrl(): string;

    /**
     * return array of request params
     * @return array
    */
    public function getRequestBody(): array;

    public function prepareResponse(string $response);
    public function prepareRequest(): Request;
}
