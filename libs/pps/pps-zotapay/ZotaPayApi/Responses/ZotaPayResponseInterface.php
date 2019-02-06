<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 14:39
 */

namespace pps\zotapay\ZotaPayApi\Responses;

/**
 *  * The type of response. May be async-form-response, validation-error, error.
 * If type equals validation-error or error, error-message and error-code parameters contain error details. .
 * @property  $type
 *
 * The error code in case of declined or error status .
 * @property  $error_code
 *
 * If status is declined or error this parameter contains the reason for decline or error details .
 * @property  $error_message
 */
interface ZotaPayResponseInterface
{
    public function getResponseBody(): array;

}
