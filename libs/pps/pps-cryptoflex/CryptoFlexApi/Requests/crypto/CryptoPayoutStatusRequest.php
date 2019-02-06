<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 14.08.18
 * Time: 15:51
 */

namespace pps\cryptoflex\CryptoFlexApi\Requests\crypto;

use pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest;

/**
 * криптовалюта кошелька, в которой совершена выплата
 * @property string $crypto_currency
 *
 * идентификатор выплаты на стороне партнера
 * @property string $partner_withdraw_id
 *
 * адрес кошелька, с которого произведена выплата
 * @property string $withdraw_address
 *
*/

class CryptoPayoutStatusRequest extends BaseRequest
{
    public $crypto_currency;
    public $partner_withdraw_id;
    public $wallet_address;

    protected $mandatoryFields = [
        'partner_withdraw_id',
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