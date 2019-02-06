<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 09.11.18
 * Time: 14:39
 */

namespace pps\cryptoflex\CryptoFlexApi\Requests\crypto;

use pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest;

class CryptoWalletBalanceRequest extends BaseRequest
{
    public $crypto_currency;
    public $wallet_address;

    protected $mandatoryFields = [
        'wallet_address',
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
