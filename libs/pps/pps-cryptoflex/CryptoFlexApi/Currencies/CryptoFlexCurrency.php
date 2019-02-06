<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 27.07.18
 * Time: 14:20
 */

namespace pps\cryptoflex\CryptoFlexApi\Currencies;

use pps\cryptoflex\CryptoFlexApi\Currencies\crypto\CurrenciesList;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexPaymentMethod;
use pps\cryptoflex\CryptoFlexApi\PaymentValidator;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexItemNotExistException;
use pps\payment\Payment;
use yii\helpers\ArrayHelper;

abstract class CryptoFlexCurrency
{
    protected $currenciesList = [];

    protected $fields = [];

    protected $currencyFieldsException = [];

    protected $supportedPayWays = [Payment::WAY_DEPOSIT => false, Payment::WAY_WITHDRAW => true];

    /**
     * @param string $currency
     * @return void
     * @throws CryptoFlexItemNotExistException
     * @throws \pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexValidationException
     */
    public function isSupportedCurrency(string $currency)
    {
        $currency = strtoupper($currency);
        PaymentValidator::stringValidate($currency, 'Currency', 3, 4);
        if (!array_key_exists($currency, $this->currenciesList)) {
            throw new CryptoFlexItemNotExistException("Currency '{$currency}' does not supported");
        }
    }

    /**
     * @param string $way
     * @throws CryptoFlexItemNotExistException
     */
    public function isSupportedWay(string $way)
    {
        if (!ArrayHelper::getValue($this->supportedPayWays, $way)) {
            throw new CryptoFlexItemNotExistException("Payment system does not supports '{$way}");
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
        }
        return ArrayHelper::getValue($this->fields, $way, []);
    }

    /**
     * @return array
     */
    public function getCurrenciesList(): array
    {
        return array_keys($this->currenciesList);
    }

    /**
     * @return array
     */
    public function getApiFields(): array
    {
        $apiFields = [];
        foreach ($this->getCurrenciesList() as $currency) {
            $apiFields[$currency][$this->getPaymentMethod()] = $this->fields;
        }
        return $apiFields;
    }

    /**
     * @return array
     * @throws CryptoFlexItemNotExistException
     */
    public static function getAllSupportedCurrencies(): array
    {
        $supportCurr = [];
        foreach (CryptoFlexPaymentMethod::LIST as $paymentMethod) {
            $supportCurr = array_merge_recursive(
                $supportCurr,
                self::getCurrencyObject($paymentMethod)->getSupportedCurrencies()
            );
        }
        return $supportCurr;
    }

    /**
     * @return array
     * @throws CryptoFlexItemNotExistException
     */
    public static function getAllApiFields(): array
    {
        $apiFields = [];
        foreach (CryptoFlexPaymentMethod::LIST as $paymentMethod) {
            $apiFields = array_merge_recursive($apiFields, self::getCurrencyObject($paymentMethod)->getApiFields());
        }
        return $apiFields;
    }

    /**
     * @param string $paymentMethod
     * @return CryptoFlexCurrency
     * @throws CryptoFlexItemNotExistException
     */
    public static function getCurrencyObject(string $paymentMethod): self
    {
        switch ($paymentMethod) {
            case CryptoFlexPaymentMethod::CRYPTO:
                return new CurrenciesList();
                break;
            default:
                throw new CryptoFlexItemNotExistException("Payment method '{$paymentMethod}' not allowed!");
                break;
        }
    }
}
