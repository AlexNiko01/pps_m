<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 14:45
 */

namespace pps\cryptoflex\CryptoFlexApi\Responses\crypto;

/**
 * уникальный идентификатор выплаты в системе CryptoFlex, в hash формате
 * @property string $withdraw_id.
 *
 *  уникальный идентификатор выплаты на стороне партнера
 * @property string $partner_withdraw_id
 *
 * необходимый мерчанту адрес для вывода средств
 * @property string $withdraw_address
 *
 * криптовалюта, в которой должен оплатить счет плательщик: ETH, BTC, BCH, LTC, DASH
 * @property string $crypto_currency
 *
 * сумма полученная на транзитный адрес 0.0099328.
 * @property float $amount
 *
 * значение рассчитанное при отправке платежа на конечный адрес 0.0000441.
 * @property float $fee_value
 *
 * статус платежа 2.
 * @property int $status
 */
class CryptoPayoutResponse extends CryptoBaseResponse
{
    public $withdraw_id;
    public $partner_withdraw_id;
    public $withdraw_address;
    public $crypto_currency;
    public $amount;
    public $fee_value;
    public $status;

    public function rules()
    {
        return [
            [
                [
                    'withdraw_id',
                    'partner_withdraw_id',
                    'withdraw_address',
                    'crypto_currency',
                    'amount',
                    'fee_value',
                    'status'
                ],
                'trim'
            ],
            [
                ['fee_value', 'amount'],
                'filter',
                'filter' =>
                function ($attribute) {
                    return (float)$attribute;
                }
            ],
        ];
    }
}
