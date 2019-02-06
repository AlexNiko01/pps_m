<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 19.07.18
 * Time: 10:11
 */

namespace pps\zotapay\ZotaPayApi\Requests;

use GuzzleHttp\Psr7\Request;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;

interface ZotaPayRequestInterface
{
    public function __toString();
    public function __construct(ZotaPayConfig $config);
    public function getMethod(): string;

    public function getRequestUrl(): string;
    public function getRequestBody(): array;

    public function prepareResponse(string $response);
    public function prepareRequest(): Request;
}
