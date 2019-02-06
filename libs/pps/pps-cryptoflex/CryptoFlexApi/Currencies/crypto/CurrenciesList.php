<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 27.07.18
 * Time: 14:37
 */

namespace pps\cryptoflex\CryptoFlexApi\Currencies\crypto;

use pps\cryptoflex\CryptoFlexApi\Currencies\CryptoFlexCurrency;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexPaymentMethod;
use pps\payment\Payment;
use yii\helpers\ArrayHelper;

class CurrenciesList extends CryptoFlexCurrency
{
    protected $currenciesList = [
        'BTC' => 'Bitcoin',
        'ETH' => 'Ethereum',
        'LTC' => 'Litecoin',
        'BCH' => 'Bitcoin Cash',
        'DASH' => 'Dash',
    ];

    protected $fields = [
        Payment::WAY_DEPOSIT => [],
        Payment::WAY_WITHDRAW => [
            'withdraw_address' => [
                'required' => true,
                'label' => 'Cryptocurrency hash adress',
            ],
        ]
    ];

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return CryptoFlexPaymentMethod::CRYPTO;
    }

    /**
     * @return array
     */
    public function getSupportedCurrencies(): array
    {
        $supportedCurrencies = [];
        $paymentMethod = $this->getPaymentMethod();
        foreach ($this->getCurrenciesList() as $currency) {
            $supportedCurrencies[$currency][$paymentMethod] = [
                'name' => ArrayHelper::getValue($this->currenciesList, $currency),
                'fields' => $this->fields,
            ];
            $supportedCurrencies[$currency][$paymentMethod] = ArrayHelper::merge(
                $supportedCurrencies[$currency][$paymentMethod],
                $this->supportedPayWays
            );
        }
        return $supportedCurrencies;
    }
}
