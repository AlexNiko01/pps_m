<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 17:25
 */

namespace pps\cryptoflex\CryptoFlexApi\Requests\crypto;

use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest;
use yii\helpers\ArrayHelper;

/**
 * криптовалюта кошелька, с которого будет совершена выплата
 * @property string $crypto_currency
 *
 * идентификатор выплаты на стороне партнера
 * @property string $partner_withdraw_id
 *
 * необходимая сумма выплаты
 * @property float $amount
 *
 * адрес кошелька, с которого будет производится выплата
 * @property string $wallet_address
 *
 * необходимый мерчанту адрес для вывода средств
 * @property string $withdraw_address
 *
 * число принятых подтверждений платежа в сети с адреса кошелька CryptoFlex на withdraw_address
 * @property int $confirmations_withdraw
 *
 * уровень комиссии сети high, medium и low
 * @property int $fee_level
 */
class CryptoPayoutRequest extends BaseRequest
{
    public $crypto_currency;
    public $partner_withdraw_id;
    public $wallet_address;
    public $withdraw_address;
    public $confirmations_withdraw;
    public $fee_level;
    public $amount;


    public function rules(): array
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                [$this->mandatoryFields, 'required'],
                [['crypto_currency',], 'string', 'min' => 3, 'max' => 4],
                [['confirmations_withdraw', 'amount'], 'number'],
                ['fee_level', 'in', 'range' => CryptoFlexConfig::$feeLevels]
            ]
        );
    }

    protected $mandatoryFields = [
        'partner_withdraw_id',
        'wallet_address',
        'withdraw_address',
        'crypto_currency',
        'amount',
        'timestamp'
    ];
}
