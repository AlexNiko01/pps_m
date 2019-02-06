<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 27.07.18
 * Time: 14:20
 */

namespace pps\zotapay\ZotaPayApi\Currencies;

use pps\zotapay\ZotaPayApi\Currencies\bank\BankCurrencies;
use pps\zotapay\ZotaPayApi\Currencies\card\CardCurrencies;
use pps\zotapay\ZotaPayApi\ZotaPayPaymentMethod;
use pps\zotapay\ZotaPayApi\PaymentValidator;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayItemNotExistException;
use pps\payment\Payment;
use yii\helpers\ArrayHelper;

abstract class ZotaPayCurrency
{
    protected $currenciesList = [];

    protected $fields = [];

    protected $currencyFieldsException = [];

    protected $supportedPayWays = [Payment::WAY_DEPOSIT => true, Payment::WAY_WITHDRAW => true];

    /**
     * @param string $currency
     * @return void
     * @throws ZotaPayItemNotExistException
     * @throws \pps\zotapay\ZotaPayApi\Exceptions\ZotaPayValidationException
     */
    public function isSupportedCurrency(string $currency)
    {
        $currency = strtoupper($currency);
        PaymentValidator::stringValidate($currency, 'Currency', 3, 3);
        if (!key_exists($currency, $this->currenciesList)) {
            throw new ZotaPayItemNotExistException("Currency '{$currency}' does not supported");
        }
    }

    /**
     * @param string $way
     * @throws ZotaPayItemNotExistException
     */
    public function isSupportedWay(string $way)
    {
        if (!ArrayHelper::getValue($this->supportedPayWays, $way)) {
            throw new ZotaPayItemNotExistException("Payment system does not supports '{$way}");
        }
    }

    /** Return supported currencies list for payment method*/
    abstract public function getSupportedCurrencies();

    /** Return current Payment method*/
    abstract public function getPaymentMethod();

    /**
     * @param string $currency
     * @param string|null $way
     * @return mixed
     */
    public function getFields(string $currency, string $way = null)
    {
        if (isset($this->currencyFieldsException[$currency][$way])) {
            return ArrayHelper::getValue($this->currencyFieldsException, "$currency . $way", []);
        };
        return ArrayHelper::getValue($this->fields, $way, []);
    }

    public function getCurrenciesList()
    {
        return array_keys($this->currenciesList);
    }

    public function getApiFields()
    {
        $apiFields = [];
        foreach ($this->getCurrenciesList() as $currency) {
            $apiFields[$currency][$this->getPaymentMethod()] = $this->fields;
        }
        return $apiFields;
    }

    /**
     * @return array
     * @throws ZotaPayItemNotExistException
     */
    public static function getAllSupportedCurrencies()
    {
        $supportCurr = [];
        foreach (ZotaPayPaymentMethod::LIST as $paymentMethod) {
            $supportCurr = array_merge_recursive(
                $supportCurr,
                self::getCurrencyClass($paymentMethod)->getSupportedCurrencies()
            );
        }
        return $supportCurr;
    }

    /**
     * @return array
     * @throws ZotaPayItemNotExistException
     */
    public static function getAllApiFields()
    {
        $apiFields = [];
        foreach (ZotaPayPaymentMethod::LIST as $paymentMethod) {
            $apiFields = array_merge_recursive($apiFields, self::getCurrencyClass($paymentMethod)->getApiFields());
        }
        return $apiFields;
    }

    /**
     * @param string $paymentMethod
     * @return ZotaPayCurrency
     * @throws ZotaPayItemNotExistException
     */
    public static function getCurrencyClass(string $paymentMethod): self
    {
        switch ($paymentMethod) {
            case ZotaPayPaymentMethod::CARD:
                return new CardCurrencies();
            case ZotaPayPaymentMethod::BANK:
                return new BankCurrencies();
            default:
                throw new ZotaPayItemNotExistException("Payment method '{$paymentMethod}' not allowed!");
                break;
        }
    }
}
