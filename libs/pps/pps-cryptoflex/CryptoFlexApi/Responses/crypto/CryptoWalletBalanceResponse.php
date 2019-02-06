<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 14:45
 */

namespace pps\cryptoflex\CryptoFlexApi\Responses\crypto;

/**
 * адрес кошелька
 * @property string $wallet_address.
 *
 * криптовалюта кошелька
 * @property float $balance
 *
 * криптовалюта кошелька
 * @property string $crypto_currency
 */
class CryptoWalletBalanceResponse extends CryptoBaseResponse
{
    public $wallet_address;
    public $balance;
    public $crypto_currency;

    public function rules()
    {
        return [
            [
                [
                    'wallet_address',
                    'balance',
                    'crypto_currency',
                ],
                'trim'
            ],
            [['balance'], 'double']
        ];
    }
}
