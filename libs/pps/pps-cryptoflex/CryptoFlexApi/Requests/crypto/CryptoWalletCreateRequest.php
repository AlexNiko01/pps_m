<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 09.11.18
 * Time: 14:39
 */

namespace pps\cryptoflex\CryptoFlexApi\Requests\crypto;

use pps\cryptoflex\CryptoFlexApi\Currencies\crypto\CurrenciesList;
use pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest;

class CryptoWalletCreateRequest extends BaseRequest
{
    public $crypto_currency;
    public $partner_id;

    protected $mandatoryFields = [
        'partner_id',
        'crypto_currency',
        'timestamp'
    ];

    public function rules(): array
    {
        return [
            [$this->mandatoryFields, 'required'],
            [['crypto_currency',], 'string', 'min' => 3, 'max' => 4],
        ];
    }
}
