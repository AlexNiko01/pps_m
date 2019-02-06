<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 27.07.18
 * Time: 14:37
 */

namespace pps\zotapay\ZotaPayApi\Currencies\bank;

use pps\zotapay\ZotaPayApi\Currencies\ZotaPayCurrency;
use pps\zotapay\ZotaPayApi\ZotaPayPaymentMethod;
use pps\payment\Payment;
use yii\helpers\ArrayHelper;

class BankCurrencies extends ZotaPayCurrency
{
    protected $currenciesList = [
        'UAH' => 'Ukrainian hryvnia',
        'RUB' => 'Russian ruble',
        'EUR' => 'Euro',
        'USD' => 'United States dollar',
        'KZT' => 'Kazakhstani tenge',
        'PLN' => 'Polish złoty',
        'CNY' => 'Chinese yuan',
    ];

    protected $supportedPayWays = [Payment::WAY_DEPOSIT => false, Payment::WAY_WITHDRAW => true];

    protected $fields = [
        Payment::WAY_DEPOSIT => [],
        Payment::WAY_WITHDRAW => [
            'account_number' => [
                'required' => true,
                'label' => 'Account Number',
                'regex' => '^\w{12, 24}$',
            ],
            'account_name' => [
                'required' => true,
                'label' => 'Account Name',
            ],
            'bank_name' => [
                'required' => true,
                'label' => 'Bank Name',
            ],
            'bank_branch' => [
                'required' => true,
                'label' => 'Bank Branch Name',
            ],
            'routing_number' => [
                'required' => false,
                'label' => 'Routing number used to identify specific bank branches in China',
            ],
        ]
    ];

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return ZotaPayPaymentMethod::BANK;
    }

    /**
     * @return array
     */
    public function getSupportedCurrencies(): array
    {
        $supportedCurrencies = [];
        foreach ($this->getCurrenciesList() as $currency) {
            $paymentMethod = $this->getPaymentMethod();
            $supportedCurrencies[$currency][$paymentMethod] = [
                'name' => 'Bank',
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
