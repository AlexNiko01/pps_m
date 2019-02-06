<?php

namespace pps\cardpay {
    use pps\payment\Payment;

    function getCurrencies($fields, $ps): array
    {
        return [
            'ALL' => [
                $ps => [
                    'name' => 'Albanian lek',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'DZD' => [
                $ps => [
                    'name' => 'Algerian dinar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'ARS' => [
                $ps => [
                    'name' => 'Argentine Peso',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'AUD' => [
                $ps => [
                    'name' => 'Australian Dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BSD' => [
                $ps => [
                    'name' => 'Bahamian dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BHD' => [
                $ps => [
                    'name' => 'Bahraini dinar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BDT' => [
                $ps => [
                    'name' => 'Bangladeshi taka',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'AMD' => [
                $ps => [
                    'name' => 'Armenian dram',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BBD' => [
                $ps => [
                    'name' => 'Barbados dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BMD' => [
                $ps => [
                    'name' => 'Bermudian dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BTN' => [
                $ps => [
                    'name' => 'Bhutanese ngultrum',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BOB' => [
                $ps => [
                    'name' => 'Boliviano',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BWP' => [
                $ps => [
                    'name' => 'Botswana pula',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BZD' => [
                $ps => [
                    'name' => 'Belize dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SBD' => [
                $ps => [
                    'name' => 'Solomon Islands dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BND' => [
                $ps => [
                    'name' => 'Brunei dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MMK' => [
                $ps => [
                    'name' => 'Myanmar kyat',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BIF' => [
                $ps => [
                    'name' => 'Burundian franc',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'KHR' => [
                $ps => [
                    'name' => 'Cambodian riel',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CAD' => [
                $ps => [
                    'name' => 'Canadian dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CVE' => [
                $ps => [
                    'name' => 'Cape Verde escudo',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'KYD' => [
                $ps => [
                    'name' => 'Cayman Islands dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'LKR' => [
                $ps => [
                    'name' => 'Sri Lankan rupee',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CLP' => [
                $ps => [
                    'name' => 'Chilean peso',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CNY' => [
                $ps => [
                    'name' => 'Chinese yuan',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'COP' => [
                $ps => [
                    'name' => 'Colombian peso',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'KMF' => [
                $ps => [
                    'name' => 'Comoro franc',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CRC' => [
                $ps => [
                    'name' => 'Costa Rican colon',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'HRK' => [
                $ps => [
                    'name' => 'Croatian kuna',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CUP' => [
                $ps => [
                    'name' => 'Cuban peso',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CZK' => [
                $ps => [
                    'name' => 'Czech koruna',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'DKK' => [
                $ps => [
                    'name' => 'Danish krone',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'DOP' => [
                $ps => [
                    'name' => 'Dominican peso',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SVC' => [
                $ps => [
                    'name' => 'Salvadoran colón',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'ETB' => [
                $ps => [
                    'name' => 'Ethiopian birr',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'ERN' => [
                $ps => [
                    'name' => 'Eritrean nakfa',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'FKP' => [
                $ps => [
                    'name' => 'Falkland Islands pound',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'FJD' => [
                $ps => [
                    'name' => 'Fiji dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'DJF' => [
                $ps => [
                    'name' => 'Djiboutian franc',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'GMD' => [
                $ps => [
                    'name' => 'Gambian dalasi',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'GIP' => [
                $ps => [
                    'name' => 'Gibraltar pound',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'GTQ' => [
                $ps => [
                    'name' => 'Guatemalan quetzal',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'GNF' => [
                $ps => [
                    'name' => 'Guinean franc',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'GYD' => [
                $ps => [
                    'name' => 'Guyanese dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'HTG' => [
                $ps => [
                    'name' => 'Haitian gourde',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'HNL' => [
                $ps => [
                    'name' => 'Honduran lempira',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'HKD' => [
                $ps => [
                    'name' => 'Hong Kong dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'HUF' => [
                $ps => [
                    'name' => 'Hungarian forint',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'ISK' => [
                $ps => [
                    'name' => 'Icelandic króna',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'INR' => [
                $ps => [
                    'name' => 'Indian rupee',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'IDR' => [
                $ps => [
                    'name' => 'Indonesian rupiah',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'IRR' => [
                $ps => [
                    'name' => 'Iranian rial',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'IQD' => [
                $ps => [
                    'name' => 'Iraqi dinar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'ILS' => [
                $ps => [
                    'name' => 'Israeli new shekel',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'JMD' => [
                $ps => [
                    'name' => 'Jamaican dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'JPY' => [
                $ps => [
                    'name' => 'Japanese yen',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'KZT' => [
                $ps => [
                    'name' => 'Kazakhstani tenge',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'JOD' => [
                $ps => [
                    'name' => 'Jordanian dinar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'KES' => [
                $ps => [
                    'name' => 'Kenyan shilling',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'KPW' => [
                $ps => [
                    'name' => 'North Korean won',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'KRW' => [
                $ps => [
                    'name' => 'South Korean won',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'KWD' => [
                $ps => [
                    'name' => 'Kuwaiti dinar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'KGS' => [
                $ps => [
                    'name' => 'Kyrgyzstani som',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'LAK' => [
                $ps => [
                    'name' => 'Lao kip',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'LBP' => [
                $ps => [
                    'name' => 'Lebanese pound',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'LSL' => [
                $ps => [
                    'name' => 'Lesotho loti',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'LRD' => [
                $ps => [
                    'name' => 'Liberian dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'LTL' => [
                $ps => [
                    'name' => 'Libyan dinar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MOP' => [
                $ps => [
                    'name' => 'Macanese pataca',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MWK' => [
                $ps => [
                    'name' => 'Malawian kwacha',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MYR' => [
                $ps => [
                    'name' => 'Malaysian ringgit',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MVR' => [
                $ps => [
                    'name' => 'Maldivian rufiyaa',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MRO' => [
                $ps => [
                    'name' => 'Mauritanian ouguiya',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MUR' => [
                $ps => [
                    'name' => 'Mauritian rupee',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MXN' => [
                $ps => [
                    'name' => 'Mexican peso',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MNT' => [
                $ps => [
                    'name' => 'Mongolian tögrög',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MDL' => [
                $ps => [
                    'name' => 'Moldovan leu',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MAD' => [
                $ps => [
                    'name' => 'Moroccan dirham',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'OMR' => [
                $ps => [
                    'name' => 'Omani rial',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'NAD' => [
                $ps => [
                    'name' => 'Namibian dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'NPR' => [
                $ps => [
                    'name' => 'Nepalese rupee',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'ANG' => [
                $ps => [
                    'name' => 'Netherlands Antillean guilder',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'AWG' => [
                $ps => [
                    'name' => 'Aruban florin',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'VUV' => [
                $ps => [
                    'name' => 'Vanuatu vatu',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'NZD' => [
                $ps => [
                    'name' => 'New Zealand dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'NIO' => [
                $ps => [
                    'name' => 'Nicaraguan córdoba',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'NGN' => [
                $ps => [
                    'name' => 'Nigerian naira',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'NOK' => [
                $ps => [
                    'name' => 'Norwegian krone',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'PKR' => [
                $ps => [
                    'name' => 'Pakistani rupee',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'PAB' => [
                $ps => [
                    'name' => 'Panamanian balboa',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'PGK' => [
                $ps => [
                    'name' => 'Papua New Guinean kina',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'PYG' => [
                $ps => [
                    'name' => 'Paraguayan guaraní',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'PEN' => [
                $ps => [
                    'name' => 'Peruvian Sol',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'PHP' => [
                $ps => [
                    'name' => 'Philippine piso',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'QAR' => [
                $ps => [
                    'name' => 'Qatari riyal',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'RUB' => [
                $ps => [
                    'name' => 'Russian ruble',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'RWF' => [
                $ps => [
                    'name' => 'Rwandan franc',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SHP' => [
                $ps => [
                    'name' => 'Saint Helena pound',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'STD' => [
                $ps => [
                    'name' => 'São Tomé and Príncipe dobra',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SAR' => [
                $ps => [
                    'name' => 'Saudi riyal',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SCR' => [
                $ps => [
                    'name' => 'Seychelles rupee',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SLL' => [
                $ps => [
                    'name' => 'Sierra Leonean leone',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SGD' => [
                $ps => [
                    'name' => 'Singapore dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'VND' => [
                $ps => [
                    'name' => 'Vietnamese đồng',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SOS' => [
                $ps => [
                    'name' => 'Somali shilling',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'ZAR' => [
                $ps => [
                    'name' => 'South African rand',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SSP' => [
                $ps => [
                    'name' => 'South Sudanese pound',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SZL' => [
                $ps => [
                    'name' => 'Swazi lilangeni',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SEK' => [
                $ps => [
                    'name' => 'Swedish krona/kronor',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CHF' => [
                $ps => [
                    'name' => 'Swiss franc',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SYP' => [
                $ps => [
                    'name' => 'Syrian pound',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'THB' => [
                $ps => [
                    'name' => 'Thai baht',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'TOP' => [
                $ps => [
                    'name' => 'Tongan paʻanga',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'TTD' => [
                $ps => [
                    'name' => 'Trinidad and Tobago dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'AED' => [
                $ps => [
                    'name' => 'United Arab Emirates dirham',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'TND' => [
                $ps => [
                    'name' => 'Tunisian dinar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'UGX' => [
                $ps => [
                    'name' => 'Ugandan shilling',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MKD' => [
                $ps => [
                    'name' => 'Macedonian denar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'EGP' => [
                $ps => [
                    'name' => 'Egyptian pound',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'GBP' => [
                $ps => [
                    'name' => 'Pound sterling',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'TZS' => [
                $ps => [
                    'name' => 'Tanzanian shilling',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'USD' => [
                $ps => [
                    'name' => 'United States dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'UYU' => [
                $ps => [
                    'name' => 'Uruguayan peso',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'UZS' => [
                $ps => [
                    'name' => 'Uzbekistan som',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'WST' => [
                $ps => [
                    'name' => 'Samoan tala',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'YER' => [
                $ps => [
                    'name' => 'Yemeni rial',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'TWD' => [
                $ps => [
                    'name' => 'New Taiwan dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CUC' => [
                $ps => [
                    'name' => 'Cuban convertible peso',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'ZWL' => [
                $ps => [
                    'name' => 'Zimbabwean dollar A/10',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BYN' => [
                $ps => [
                    'name' => 'Belarusian ruble',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'TMT' => [
                $ps => [
                    'name' => 'Turkmenistan manat',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'GHS' => [
                $ps => [
                    'name' => 'Ghanaian cedi',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'VEF' => [
                $ps => [
                    'name' => 'Venezuelan bolívar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SDG' => [
                $ps => [
                    'name' => 'Sudanese pound',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'UYI' => [
                $ps => [
                    'name' => 'Uruguay Peso en Unidades Indexadas (URUIURUI) (funds code)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'RSD' => [
                $ps => [
                    'name' => 'Serbian dinar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MZN' => [
                $ps => [
                    'name' => 'Mozambican metical',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'AZN' => [
                $ps => [
                    'name' => 'Azerbaijani manat',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'RON' => [
                $ps => [
                    'name' => 'Romanian leu',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CHE' => [
                $ps => [
                    'name' => 'WIR Euro (complementary currency)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CHW' => [
                $ps => [
                    'name' => 'WIR Franc (complementary currency)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'TRY' => [
                $ps => [
                    'name' => 'Turkish lira',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XAF' => [
                $ps => [
                    'name' => 'CFA franc BEAC',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XCD' => [
                $ps => [
                    'name' => 'East Caribbean dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XOF' => [
                $ps => [
                    'name' => 'CFA franc BCEAO',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XPF' => [
                $ps => [
                    'name' => 'CFP franc (franc Pacifique)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XBA' => [
                $ps => [
                    'name' => 'European Composite Unit (EURCO) (bond market unit)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XBB' => [
                $ps => [
                    'name' => 'European Monetary Unit (E.M.U.-6) (bond market unit)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XBC' => [
                $ps => [
                    'name' => 'European Unit of Account 9 (E.U.A.-9) (bond market unit)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XBD' => [
                $ps => [
                    'name' => 'European Unit of Account 17 (E.U.A.-17) (bond market unit)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XAU' => [
                $ps => [
                    'name' => 'Gold (one troy ounce)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XDR' => [
                $ps => [
                    'name' => 'Special drawing rights',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XAG' => [
                $ps => [
                    'name' => 'Silver (one troy ounce)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XPT' => [
                $ps => [
                    'name' => 'Platinum (one troy ounce)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XTS' => [
                $ps => [
                    'name' => 'Code reserved for testing purposes',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XPD' => [
                $ps => [
                    'name' => 'Palladium (one troy ounce)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XUA' => [
                $ps => [
                    'name' => 'ADB Unit of Account',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'ZMW' => [
                $ps => [
                    'name' => 'Zambian kwacha',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'SRD' => [
                $ps => [
                    'name' => 'Surinamese dollar',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MGA' => [
                $ps => [
                    'name' => 'Malagasy ariary',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'COU' => [
                $ps => [
                    'name' => 'Unidad de Valor Real (UVR) (funds code)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'AFN' => [
                $ps => [
                    'name' => 'Afghan afghani',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'TJS' => [
                $ps => [
                    'name' => 'Tajikistani somoni',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'AOA' => [
                $ps => [
                    'name' => 'Angolan kwanza',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BGN' => [
                $ps => [
                    'name' => 'Bulgarian lev',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CDF' => [
                $ps => [
                    'name' => 'Congolese franc',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BAM' => [
                $ps => [
                    'name' => 'Bosnia and Herzegovina convertible mark',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'EUR' => [
                $ps => [
                    'name' => 'Euro',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'MXV' => [
                $ps => [
                    'name' => 'Mexican Unidad de Inversion (UDI) (funds code)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'UAH' => [
                $ps => [
                    'name' => 'Ukrainian hryvnia',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'GEL' => [
                $ps => [
                    'name' => 'Georgian lari',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BOV' => [
                $ps => [
                    'name' => 'Bolivian Mvdol (funds code)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'PLN' => [
                $ps => [
                    'name' => 'Polish złoty',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'BRL' => [
                $ps => [
                    'name' => 'Brazilian real',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'CLF' => [
                $ps => [
                    'name' => 'Unidad de Fomento (funds code)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XSU' => [
                $ps => [
                    'name' => 'SUCRE',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'USN' => [
                $ps => [
                    'name' => 'United States dollar (next day) (funds code)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'USS' => [
                $ps => [
                    'name' => 'United States dollar (same day) (funds code)',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
            'XXX' => [
                $ps => [
                    'name' => 'no currency ',
                    'fields' => $fields,
                    Payment::WAY_DEPOSIT => true,
                    Payment::WAY_WITHDRAW => true,
                ],
            ],
        ];
    }
}