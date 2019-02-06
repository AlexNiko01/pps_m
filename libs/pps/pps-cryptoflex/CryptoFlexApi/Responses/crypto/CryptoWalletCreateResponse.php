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
 */
class CryptoWalletCreateResponse extends CryptoBaseResponse
{
    public $wallet_address;

    public function rules()
    {
        return [['wallet_address', 'string']];
    }
}
