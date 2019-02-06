<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 14:45
 */

namespace pps\cryptoflex\CryptoFlexApi\Responses\crypto;

/**
 * уникальный идентификатор счета в системе CryptoFlex, в формате hash
 * @property string $invoice_id.
 *
 *  идентификатор на стороне мерчанта
 * @property string $partner_invoice_id
 *
 * сгенерированый транзитный адрес для приёма платежа в системе CryptoFlex используется один раз для одной
 * сущности Invoice
 * @property string $address
 *
 * криптовалюта, в которой должен оплатить счет плательщик: ETH, BTC, BCH, LTC, DASH
 * @property string $crypto_currency
 */
class CryptoOrderResponse extends CryptoBaseResponse
{
    public $invoice_id;
    public $partner_invoice_id;
    public $address;
    public $crypto_currency;

    public function rules()
    {
        return [
            [['partner_invoice_id', 'invoice_id', 'address', 'crypto_currency'], 'trim']
        ];
    }
}
