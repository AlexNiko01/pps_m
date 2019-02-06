<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 27.07.18
 * Time: 14:37
 */

namespace pps\zotapay\ZotaPayApi\Currencies\card;

use pps\zotapay\ZotaPayApi\Currencies\ZotaPayCurrency;
use pps\zotapay\ZotaPayApi\ZotaPayPaymentMethod;
use pps\payment\Payment;
use yii\helpers\ArrayHelper;

class CardCurrencies extends ZotaPayCurrency
{
    protected $currenciesList = [
        'UAH' => 'Ukrainian hryvnia',
        'RUB' => 'Russian ruble',
        'EUR' => 'Euro',
        'USD' => 'United States dollar',
        'KZT' => 'Kazakhstani tenge',
        'PLN' => 'Polish złoty',
        'CNY' => 'Chinese yuan',
/*        'ALL' => 'Albanian lek',
        'DZD' => 'Algerian dinar',
        'ARS' => 'Argentine Peso',
        'AUD' => 'Australian Dollar',
        'BSD' => 'Bahamian dollar',
        'BHD' => 'Bahraini dinar',
        'BDT' => 'Bangladeshi taka',
        'AMD' => 'Armenian dram',
        'BBD' => 'Barbados dollar',
        'BMD' => 'Bermudian dollar',
        'BTN' => 'Bhutanese ngultrum',
        'BOB' => 'Boliviano',
        'BWP' => 'Botswana pula',
        'BZD' => 'Belize dollar',
        'SBD' => 'Solomon Islands dollar',
        'BND' => 'Brunei dollar',
        'MMK' => 'Myanmar kyat',
        'BIF' => 'Burundian franc',
        'KHR' => 'Cambodian riel',
        'CAD' => 'Canadian dollar',
        'CVE' => 'Cape Verde escudo',
        'KYD' => 'Cayman Islands dollar',
        'LKR' => 'Sri Lankan rupee',
        'CLP' => 'Chilean peso',
        'COP' => 'Colombian peso',
        'KMF' => 'Comoro franc',
        'CRC' => 'Costa Rican colon',
        'HRK' => 'Croatian kuna',
        'CUP' => 'Cuban peso',
        'CZK' => 'Czech koruna',
        'DKK' => 'Danish krone',
        'DOP' => 'Dominican peso',
        'SVC' => 'Salvadoran colón',
        'ETB' => 'Ethiopian birr',
        'ERN' => 'Eritrean nakfa',
        'FKP' => 'Falkland Islands pound',
        'FJD' => 'Fiji dollar',
        'DJF' => 'Djiboutian franc',
        'GMD' => 'Gambian dalasi',
        'GIP' => 'Gibraltar pound',
        'GTQ' => 'Guatemalan quetzal',
        'GNF' => 'Guinean franc',
        'GYD' => 'Guyanese dollar',
        'HTG' => 'Haitian gourde',
        'HNL' => 'Honduran lempira',
        'HKD' => 'Hong Kong dollar',
        'HUF' => 'Hungarian forint',
        'ISK' => 'Icelandic króna',
        'INR' => 'Indian rupee',
        'IDR' => 'Indonesian rupiah',
        'IRR' => 'Iranian rial',
        'IQD' => 'Iraqi dinar',
        'ILS' => 'Israeli new shekel',
        'JMD' => 'Jamaican dollar',
        'JPY' => 'Japanese yen',
        'JOD' => 'Jordanian dinar',
        'KES' => 'Kenyan shilling',
        'KPW' => 'North Korean won',
        'KRW' => 'South Korean won',
        'KWD' => 'Kuwaiti dinar',
        'KGS' => 'Kyrgyzstani som',
        'LAK' => 'Lao kip',
        'LBP' => 'Lebanese pound',
        'LSL' => 'Lesotho loti',
        'LRD' => 'Liberian dollar',
        'LTL' => 'Libyan dinar',
        'MOP' => 'Macanese pataca',
        'MWK' => 'Malawian kwacha',
        'MYR' => 'Malaysian ringgit',
        'MVR' => 'Maldivian rufiyaa',
        'MRO' => 'Mauritanian ouguiya',
        'MUR' => 'Mauritian rupee',
        'MXN' => 'Mexican peso',
        'MNT' => 'Mongolian tögrög',
        'MDL' => 'Moldovan leu',
        'MAD' => 'Moroccan dirham',
        'OMR' => 'Omani rial',
        'NAD' => 'Namibian dollar',
        'NPR' => 'Nepalese rupee',
        'ANG' => 'Netherlands Antillean guilder',
        'AWG' => 'Aruban florin',
        'VUV' => 'Vanuatu vatu',
        'NZD' => 'New Zealand dollar',
        'NIO' => 'Nicaraguan córdoba',
        'NGN' => 'Nigerian naira',
        'NOK' => 'Norwegian krone',
        'PKR' => 'Pakistani rupee',
        'PAB' => 'Panamanian balboa',
        'PGK' => 'Papua New Guinean kina',
        'PYG' => 'Paraguayan guaraní',
        'PEN' => 'Peruvian Sol',
        'PHP' => 'Philippine piso',
        'QAR' => 'Qatari riyal',
        'RWF' => 'Rwandan franc',
        'SHP' => 'Saint Helena pound',
        'STD' => 'São Tomé and Príncipe dobra',
        'SAR' => 'Saudi riyal',
        'SCR' => 'Seychelles rupee',
        'SLL' => 'Sierra Leonean leone',
        'SGD' => 'Singapore dollar',
        'VND' => 'Vietnamese đồng',
        'SOS' => 'Somali shilling',
        'ZAR' => 'South African rand',
        'SSP' => 'South Sudanese pound',
        'SZL' => 'Swazi lilangeni',
        'SEK' => 'Swedish krona/kronor',
        'CHF' => 'Swiss franc',
        'SYP' => 'Syrian pound',
        'THB' => 'Thai baht',
        'TOP' => 'Tongan paʻanga',
        'TTD' => 'Trinidad and Tobago dollar',
        'AED' => 'United Arab Emirates dirham',
        'TND' => 'Tunisian dinar',
        'UGX' => 'Ugandan shilling',
        'MKD' => 'Macedonian denar',
        'EGP' => 'Egyptian pound',
        'GBP' => 'Pound sterling',
        'TZS' => 'Tanzanian shilling',
        'UYU' => 'Uruguayan peso',
        'UZS' => 'Uzbekistan som',
        'WST' => 'Samoan tala',
        'YER' => 'Yemeni rial',
        'TWD' => 'New Taiwan dollar',
        'CUC' => 'Cuban convertible peso',
        'ZWL' => 'Zimbabwean dollar A/10',
        'BYN' => 'Belarusian ruble',
        'TMT' => 'Turkmenistan manat',
        'GHS' => 'Ghanaian cedi',
        'VEF' => 'Venezuelan bolívar',
        'SDG' => 'Sudanese pound',
        'UYI' => 'Uruguay Peso en Unidades Indexadas (URUIURUI) (funds code)',
        'RSD' => 'Serbian dinar',
        'MZN' => 'Mozambican metical',
        'AZN' => 'Azerbaijani manat',
        'RON' => 'Romanian leu',
        'CHE' => 'WIR Euro (complementary currency)',
        'CHW' => 'WIR Franc (complementary currency)',
        'TRY' => 'Turkish lira',
        'XAF' => 'CFA franc BEAC',
        'XCD' => 'East Caribbean dollar',
        'XOF' => 'CFA franc BCEAO',
        'XPF' => 'CFP franc (franc Pacifique)',
        'XBA' => 'European Composite Unit (EURCO) (bond market unit)',
        'XBB' => 'European Monetary Unit (E.M.U.-6) (bond market unit)',
        'XBC' => 'European Unit of Account 9 (E.U.A.-9) (bond market unit)',
        'XBD' => 'European Unit of Account 17 (E.U.A.-17) (bond market unit)',
        'XAU' => 'Gold (one troy ounce)',
        'XDR' => 'Special drawing rights',
        'XAG' => 'Silver (one troy ounce)',
        'XPT' => 'Platinum (one troy ounce)',
        'XTS' => 'Code reserved for testing purposes',
        'XPD' => 'Palladium (one troy ounce)',
        'XUA' => 'ADB Unit of Account',
        'ZMW' => 'Zambian kwacha',
        'SRD' => 'Surinamese dollar',
        'MGA' => 'Malagasy ariary',
        'COU' => 'Unidad de Valor Real (UVR) (funds code)',
        'AFN' => 'Afghan afghani',
        'TJS' => 'Tajikistani somoni',
        'AOA' => 'Angolan kwanza',
        'BGN' => 'Bulgarian lev',
        'CDF' => 'Congolese franc',
        'BAM' => 'Bosnia and Herzegovina convertible mark',
        'MXV' => 'Mexican Unidad de Inversion (UDI) (funds code)',
        'GEL' => 'Georgian lari',
        'BOV' => 'Bolivian Mvdol (funds code)',
        'BRL' => 'Brazilian real',
        'CLF' => 'Unidad de Fomento (funds code)',
        'XSU' => 'SUCRE',
        'USN' => 'United States dollar (next day) (funds code)',
        'USS' => 'United States dollar (same day) (funds code)',
        'XXX' => 'no currency ',
*/
    ];

    protected $supportedPayWays = [Payment::WAY_DEPOSIT => true, Payment::WAY_WITHDRAW => false];

    protected $fields = [
        Payment::WAY_DEPOSIT => [
            'email' => [
                'required' => true,
                'label' => 'Customer email address',
                'regex' => '^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$',
            ],
            'first_name' => [
                'required' => true,
                'label' => 'Customer first name',
                'regex' => '^[a-zA-Z.-]{1,50}$',
            ],
            'last_name' => [
                'required' => true,
                'label' => 'Customer last name',
                'regex' => '^[a-zA-Z.-]{1,50}$',
            ],
            'address1' => [
                'required' => true,
                'label' => 'Address line',
                'regex' => '^[a-zA-Z.-]{1,50}$',
            ],
            'city' => [
                'required' => true,
                'label' => 'City',
                'regex' => '^[a-zA-Z.-]{1,50}$',
            ],
            'zip_code' => [
                'required' => true,
                'label' => 'Zip Code',
                'regex' => '^[0-9a-zA-Z]{1,10}$',
            ],
            'country' => [
                'required' => true,
                'label' => 'Country code',
                'regex' => '^[a-zA-Z]{2}$',
            ],
            'phone' => [
                'required' => true,
                'label' => 'Phone',
                'regex' => '^[+0-9]{1.15}$',
            ],
            'ipaddress' => [
                'required' => true,
                'label' => 'Customer IP',
                'regex' => '^[0-9.]{7,20}$',
            ],
        ],
        Payment::WAY_WITHDRAW => [
//            'destination_card_no' => [
//                'required' => true,
//                'label' => 'Card number',
//                'regex' => '^[0-9]{16,19}$',
//            ],
        ]
    ];

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return ZotaPayPaymentMethod::CARD;
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
                'name' => 'Банковская карта',
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
