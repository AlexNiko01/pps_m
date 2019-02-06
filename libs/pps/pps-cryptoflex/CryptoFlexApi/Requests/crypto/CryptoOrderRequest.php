<?php

namespace pps\cryptoflex\CryptoFlexApi\Requests\crypto;

use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest;
use yii\helpers\ArrayHelper;

/**
 * криптовалюта, в которой должен оплатить счет плательщик: ETH, BTC, BCH, LTC, DASH
 * @property string $crypto_currency
 *
 * идентификатор пользователя на стороне CryptoFlex, получаемый при регистрации
 * @property string $partner_id
 * идентификатор на стороне партнера
 * @property string $partner_invoice_id
 *
 * необходимый URL, по которому будет отправлен колбэк
 * @property string $callback
 *
 * необходимый мерчанту адрес (конечный), на который будут пересылаться средства.
 * hash adress, формат зависит от криптовалюты
 * @property string $payout_address
 *
 * число принятых подтверждений платежа в сети от плательщика на транзитный адрес - по умолчанию - для ETH,
 * DASH - 6 подтверждений, для BTC, BCH, LTC - 3 подтверждения
 * @property int $confirmations_trans
 *
 * число принятых подтверждений платежа в сети от транзитного на конечный адрес -
 * по умолчанию - для DASH - 3 подтверждения, для ETH, BTC, BCH, LTC - 2 подтверждения
 * @property int $confirmations_payout
 *
 * уровень комиссии сети high, medium и low - по умолчанию - medium
 * @property string $fee_level
 */
class CryptoOrderRequest extends BaseRequest
{
    public $crypto_currency;
    public $partner_id;
    public $partner_invoice_id;
    public $callback;
    public $payout_address;
    public $confirmations_trans;
    public $confirmations_payout;
    public $fee_level;

    public function rules(): array
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                [$this->mandatoryFields, 'required'],
                [['crypto_currency',], 'string', 'min' => 3, 'max' => 4],
                [['confirmations_trans', 'confirmations_payout'], 'number'],
                ['fee_level', 'in', 'range' => CryptoFlexConfig::$feeLevels]
            ]
        );
    }

    protected $mandatoryFields = [
        'partner_id',
        'partner_invoice_id',
        'payout_address',
        'callback',
        'crypto_currency',
        'timestamp'
    ];
}
