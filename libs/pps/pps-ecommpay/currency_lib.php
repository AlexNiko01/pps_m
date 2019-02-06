<?php

return [
    'RUB' => [
        'card' => [
            'name' => 'Card',
            'url' => 'card',
            'fields' => [
                'deposit' => [
                    'card' => [
                        'label' => 'Номер банковской карты Пользователя',
                        'regex' => '^[0-9]{16,19}$',
                        'example' => '1234123412341234',
                        'type' => 'text'
                    ],
                    'exp_month' => [
                        'label' => 'Месяц срока окончания действия карты',
                        'regex' => '^(0+[1-9])|(1+[0-2]))$',
                        'example' => '01',
                        'type' => 'text',
                    ],
                    'exp_year' => [
                        'label' => 'Год срока окончания действия карты',
                        'regex' => '^20+[0-9]{2}$',
                        'example' => '2018',
                        'type' => 'text',
                    ],
                    'cvv' => [
                        'label' => 'CVV код карты',
                        'regex' => '^[0-9]{3}$',
                        'example' => '123',
                        'type' => 'text',
                    ],
                    'holder' => [
                        'label' => 'Имя держателя (как на карте)',
                        'regex' => '^[a-zA-Z\s-]{4,256}$',
                        'example' => 'IVAN IVANOV',
                        'type' => 'text',
                    ],
                    /*'email' => [
                        'label' => 'E-mail держателя карты',
                        'regex' => '',
                        'example' => 'example@gmail.com',
                        'type' => 'email',
                    ],
                    'billing_phone' => [
                        'label' => 'Номер телефона держателя карты',
                        'regex' => '\d{5,32}',
                        'example' => '4951234567',
                        'type' => 'text',
                    ],
                    'billing_city' => [
                        'label' => 'Город расчетного адреса держателя карты',
                        'regex' => '^[а-яА-Яa-zA-ZёЁ\s-]{3,256}$',
                        'example' => 'Москва',
                        'type' => 'text',
                    ],
                    'billing_postal' => [
                        'label' => 'Почтовый индекс держателя карты',
                        'regex' => '^\d{4,6}$',
                        'example' => '123456',
                        'type' => 'text',
                    ],
                    'billing_address' => [
                        'label' => 'Расчетный адрес держателя карты',
                        'regex' => '^[а-яА-Яa-zA-ZёЁ\s-,]{3,512}$',
                        'example' => 'Ленина, 5',
                        'type' => 'text',
                    ],
                    'billing_country' => [
                        'label' => 'Страна держателя карты (Alpha-2 ISO 3166-1)',
                        'regex' => '^[A-Z]{2,3}$',
                        'example' => 'RU',
                        'type' => 'text',
                    ],
                    'billing_region' => [
                        'label' => 'Регион держателя карты (ISO 3166-2)',
                        'regex' => '^[A-Z-]{4,6}$',
                        'example' => 'RU-MOW',
                        'type' => 'text',
                    ]*/

                ],
                'w_hidden' => [
                    'action' => 'ym_payout'
                ]
            ],
            'deposit' => true
        ],
        'yandex' => [
            'name' => 'Яндекс.Деньги',
            'url' => 'yandex',
            'fields' => [
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Номер кошелька пользователя',
                        'regex' => '',
                        'example' => '45698745645',
                        'type' => 'text'
                    ]
                ],
                'w_hidden' => [
                    'action' => 'ym_payout'
                ]
            ],
            'withdraw' => true
        ],
        'qiwi' => [
            'name' => 'QIWI',
            'url' => 'qiwi',
            'fields' => [
                'deposit' => [
                    'account_number' => [
                        'label' => 'Номер кошелька пользователя',
                        'regex' => '^+(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$',
                        'example' => '9031111111',
                        'type' => 'text'
                    ]
                ],
                'd_hidden' => [
                    'lifetime' => null,
                    'action' => 'qiwi_create_invoice'
                ],
                'w_hidden' => [
                    'action' => 'qiwi_payout'
                ],
                'withdraw' => [
                    'account_number' => [
                        'label' => 'Номер кошелька пользователя',
                        'regex' => '^+(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$',
                        'example' => '9031111111',
                        'type' => 'text'
                    ]
                ],
            ],
            'deposit' => true,
            'withdraw' => true
        ],
        'webmoney' => [
            'name' => 'WebMoney',
            'url' => 'webmoney',
            'fields' => [
                'deposit' => [
                    'customer_wmid' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => '123456789012',
                        'type' => 'text'
                    ]
                ],
                'd_hidden' => [
                    'protection_period' => 0,
                    'expiration_period' => 0,
                    'payment_type_id' => 11,
                    'action' => 'wmcreateinvoice'
                ],
                'w_hidden' => [
                    'action' => 'wmpayout'
                ],
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => 'R880329336884',
                        'type' => 'text'
                    ]
                ],
            ],
            'deposit' => true,
            'withdraw' => true
        ],
        'boleto' => [
            'name' => 'Boleto',
            'url' => 'boleto',
            'fields' => [
                'deposit' => [
                    'name' => [
                        'label' => 'Имя, фамилия пользователя',
                        'regex' => '^[a-zA-Z\s-]{3,256}$',
                        'example' => 'Ivan Lolivier',
                        'type' => 'text'
                    ],
                    'email' => [
                        'label' => 'E-mail адрес пользователя',
                        'regex' => '',
                        'example' => 'IvanLolivier@gmail.com',
                        'type' => 'email'
                    ],
                    'birthdate' => [
                        'label' => 'Дата рождения пользователя',
                        'regex' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$',
                        'example' => '1986-04-26',
                        'type' => 'date'
                    ],
                    'document' => [
                        'label' => 'Номер CPF (бразильского удостоверения личности) пользователя или DNI (Аргентина), либо ID для других стран',
                        'regex' => '^[a-zA-Z0-9]{3,14}$',
                        'example' => '12345678910',
                        'type' => 'text'
                    ],
                    'address' => [
                        'label' => 'Домашний адрес пользователя',
                        'regex' => '^[a-zA-Z0-9\s-]{6,100}$',
                        'example' => 'bonavita 1225',
                        'type' => 'text'
                    ],
                    'country_id' => [
                        'label' => 'Страна проживания пользователя (согласно ISO 3166)',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'BR',
                        'type' => 'text'
                    ],
                    'city' => [
                        'label' => 'Страна проживания пользователя (согласно ISO 3166)',
                        'regex' => '^[a-zA-Z0-9\s-]{3,30}$',
                        'example' => 'Curitiba',
                        'type' => 'text'
                    ],
                    'state' => [
                        'label' => 'Код штата проживания пользователя, например: SP, PR, RJ',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'PR',
                        'type' => 'text'
                    ],
                    'zip' => [
                        'label' => 'Индекс пользователя',
                        'regex' => '^[0-9]{3,6}$',
                        'example' => '11300',
                        'type' => 'text'
                    ],
                    'phone' => [
                        'label' => 'Номер мобильного телефона пользователя',
                        'regex' => '^[0-9]{6,15}$',
                        'example' => '099123456',
                        'type' => 'text'
                    ],
                    'payment_type_id' => [
                        'label' => 'Тип платежного инструмента',
                        'regex' => '^(63|73|74)$',
                        'example' => '63',
                        'type' => 'select',
                        'values' => [
                            '63' => 'boleto voucher',
                            '73' => 'boleto bank',
                            '74' => 'boleto direct debit',
                        ]
                    ],
                    'bank_code_id' => [
                        'label' => 'Код банка',
                        'regex' => '^[0-9]{1,2}$',
                        'example' => '5',
                        'type' => 'select',
                        'values' => [
                            '1' => 'Itaú',
                            '2' => 'Boleto',
                            '3' => 'Bradesco',
                            '4' => 'Banco do Brasil',
                            '5' => 'HSBC',
                            '6' => 'Santander',
                            '7' => 'BCP',
                            '8' => 'Inter bank',
                            '9' => 'BBVA',
                            '10' => 'Pago efectivo',
                            '11' => 'Bancomer',
                            '12' => 'Banamex',
                            '13' => 'Santander Mexico',
                            '14' => 'OXXO',
                            '15' => 'Redpagos',
                            '16' => 'Banrisul',
                            '17' => 'Caixa',
                            '18' => 'Visa',
                            '19' => 'MasterCard',
                            '20' => 'Elo',
                            '21' => 'Diners Club',
                            '22' => 'Hipercard',
                            '23' => 'Cartao MercadoLivre',
                            '24' => 'American Express',
                            '25' => 'Visa Debit',
                            '26' => 'MasterCard Debit',
                        ]
                    ]
                ],
                'd_hidden' => [
                    'term_url' => null,
                    'action' => 'create_invoice'
                ]
            ],
            'deposit' => true
        ],
        'egopay' => [
            'name' => 'EgoPay',
            'url' => 'egoPay',
            'fields' => [
                'deposit' => [
                    'name' => [
                        'label' => 'Имя, фамилия пользователя',
                        'regex' => '^[a-zA-Z\s-]{3,256}$',
                        'example' => 'Ivan Lolivier',
                        'type' => 'text'
                    ],
                    'email' => [
                        'label' => 'E-mail адрес пользователя',
                        'regex' => '',
                        'example' => 'IvanLolivier@gmail.com',
                        'type' => 'email'
                    ],
                    'birthdate' => [
                        'label' => 'Дата рождения пользователя',
                        'regex' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$',
                        'example' => '1986-04-26',
                        'type' => 'date'
                    ],
                    'document' => [
                        'label' => 'Номер CPF (бразильского удостоверения личности) пользователя или DNI (Аргентина), либо ID для других стран',
                        'regex' => '^[a-zA-Z0-9]{3,14}$',
                        'example' => '12345678910',
                        'type' => 'text'
                    ],
                    'address' => [
                        'label' => 'Домашний адрес пользователя',
                        'regex' => '^[a-zA-Z0-9\s-]{6,100}$',
                        'example' => 'bonavita 1225',
                        'type' => 'text'
                    ],
                    'country_id' => [
                        'label' => 'Страна проживания пользователя (согласно ISO 3166)',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'BR',
                        'type' => 'text'
                    ],
                    'city' => [
                        'label' => 'Страна проживания пользователя (согласно ISO 3166)',
                        'regex' => '^[a-zA-Z0-9\s-]{3,30}$',
                        'example' => 'Curitiba',
                        'type' => 'text'
                    ],
                    'state' => [
                        'label' => 'Код штата проживания пользователя, например: SP, PR, RJ',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'PR',
                        'type' => 'text'
                    ],
                    'zip' => [
                        'label' => 'Индекс пользователя',
                        'regex' => '^[0-9]{3,6}$',
                        'example' => '11300',
                        'type' => 'text'
                    ],
                    'phone' => [
                        'label' => 'Номер мобильного телефона пользователя',
                        'regex' => '^[0-9]{6,15}$',
                        'example' => '099123456',
                        'type' => 'text'
                    ],
                ],
                'd_hidden' => [
                    'term_url' => null,
                    'payment_type_id' => 75,
                    'bank_code_id' => 27,
                    'action' => 'create_invoice'
                ]
            ],
            'deposit' => true,
        ],
        'monetaru' => [
            'name' => 'Moneta.ru',
            'url' => 'monetaRu',
            'fields' => [
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Идентификатор кошелька в конечной платежной системе',
                        'regex' => '',
                        'example' => '',
                        'type' => 'text'
                    ]
                ],
                'w_hidden' => [
                    'action' => 'moneta_ru_payout'
                ]
            ],
            'withdraw' => true,
        ],
        'neteller' => [
            'name' => 'Neteller',
            'url' => 'neteller',
            'fields' => [
                'deposit' => [
                    'customer_purse' => [
                        'label' => 'Account ID или email пользователя',
                        'regex' => '',
                        'example' => '',
                        'type' => 'text'
                    ],
                    'secure_id' => [
                        'label' => 'Secure ID пользователя',
                        'regex' => '^[0-9]{6}$',
                        'example' => '012345',
                        'type' => 'text'
                    ],
                ],
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Account ID или email пользователя',
                        'regex' => '',
                        'example' => '',
                        'type' => 'text'
                    ],
                ],
                'w_hidden' => [
                    'action' => 'neteller_payout'
                ],
                'd_hidden' => [
                    'action' => 'neteller_payment'
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
        ],
        'paysafecard' => [
            'name' => 'Paysafecard',
            'url' => 'paysafecard',
            'fields' => [
                'd_hidden' => [
                    'ok_url' => null,
                    'nok_url' => null,
                    'action' => 'create_invoice'
                ]
            ],
            'deposit' => true,
        ],
        'scrill' => [
            'name' => 'Skrill',
            'url' => 'moneybookers',
            'fields' => [
                'withdraw' => [
                    'email' => [
                        'label' => 'Email пользователя в системе MoneyBookers',
                        'regex' => '',
                        'example' => 'customer@gmail.com',
                        'type' => 'email'
                    ],
                ],
                'w_hidden' => [
                    'email_subject' => null,
                    'action' => 'payout'
                ]
            ],
            'withdraw' => true,
        ],
        'trustly' => [
            'name' => 'Trustly',
            'url' => 'trustly',
            'fields' => [
                'w_hidden' => [
                    'redirection_url' => null,
                    'action' => 'payout'
                ]
            ],
            'withdraw' => true,
        ],
        'ecopayz' => [
            'name' => 'EcoPayZ',
            'url' => 'ecoPayz',
            'fields' => [
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Account ID или email пользователя',
                        'regex' => '',
                        'example' => '1100379752',
                        'type' => 'text'
                    ],
                ],
                'w_hidden' => [
                    'action' => 'payout'
                ]
            ],
            'withdraw' => true,
        ],
        'wala' => [
            'name' => 'Wire Acquiring Latin America',
            'url' => 'bankAccount',
            'fields' => [
                'withdraw' => [
                    'beneficiary' => [
                        'label' => 'Полное имя пользователя',
                        'regex' => '^[a-zA-Z\s-]{4,100}$',
                        'example' => 'Ivan Lolivier',
                        'type' => 'text'
                    ],
                    'beneficiary_id' => [
                        'label' => 'Идентификатор пользователя',
                        'regex' => '^[a-zA-Z0-9-]{4,16}$',
                        'example' => '2565485',
                        'type' => 'text'
                    ],
                    'document' => [
                        'label' => 'Номер CPF пользователя или DNI (Аргентина), либо ID для других стран',
                        'regex' => '^[a-zA-Z0-9-]{4,100}$',
                        'example' => '12345678910',
                        'type' => 'text'
                    ],
                    'country_id' => [
                        'label' => 'Страна проживания пользователя (согласно ISO 3166)',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'BR',
                        'type' => 'text'
                    ],
                    'bank' => [
                        'label' => 'Название банка пользователя',
                        'regex' => '^[a-zA-Z\s-]{3,45}$',
                        'example' => 'Santander',
                        'type' => 'text'
                    ],
                    'bank_branch' => [
                        'label' => 'Код отделения банка',
                        'regex' => '^[a-zA-Z0-9\s-]{3,45}$',
                        'example' => 'Santander',
                        'type' => 'text'
                    ],
                    'bank_account' => [
                        'label' => 'Номер банковского счета пользователя',
                        'regex' => '^[a-zA-Z0-9-]{3,45}$',
                        'example' => 'Santander',
                        'type' => 'text'
                    ],
                    'account_type' => [
                        'label' => 'Номер банковского счета пользователя',
                        'regex' => '^(C|S)$',
                        'example' => 'C',
                        'type' => 'select',
                        'values' => [
                            'C' => 'Текущий счет',
                            'S' => 'Сберегательный счет',
                        ]
                    ],
                    'iban' => [
                        'label' => 'IBAN (международный номер банковского счёта)',
                        'regex' => '^[a-zA-Z0-9-]{3,45}$',
                        'example' => 'ABC123000000',
                        'type' => 'text',
                    ]
                ],
                'deposit' => [
                    'name' => [
                        'label' => 'Имя, фамилия пользователя',
                        'regex' => '^[a-zA-Z\s-]{3,256}$',
                        'example' => 'Ivan Lolivier',
                        'type' => 'text'
                    ],
                    'email' => [
                        'label' => 'E-mail адрес пользователя',
                        'regex' => '',
                        'example' => 'IvanLolivier@gmail.com',
                        'type' => 'email'
                    ],
                    'birthdate' => [
                        'label' => 'Дата рождения пользователя',
                        'regex' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$',
                        'example' => '1986-04-26',
                        'type' => 'date'
                    ],
                    'document' => [
                        'label' => 'Номер CPF (бразильского удостоверения личности) пользователя или DNI (Аргентина), либо ID для других стран',
                        'regex' => '^[a-zA-Z0-9]{3,14}$',
                        'example' => '12345678910',
                        'type' => 'text'
                    ],
                    'address' => [
                        'label' => 'Домашний адрес пользователя',
                        'regex' => '^[a-zA-Z0-9\s-]{6,100}$',
                        'example' => 'bonavita 1225',
                        'type' => 'text'
                    ],
                    'country_id' => [
                        'label' => 'Страна проживания пользователя (согласно ISO 3166)',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'BR',
                        'type' => 'text'
                    ],
                    'city' => [
                        'label' => 'Страна проживания пользователя (согласно ISO 3166)',
                        'regex' => '^[a-zA-Z0-9\s-]{3,30}$',
                        'example' => 'Curitiba',
                        'type' => 'text'
                    ],
                    'state' => [
                        'label' => 'Код штата проживания пользователя, например: SP, PR, RJ',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'PR',
                        'type' => 'text'
                    ],
                    'zip' => [
                        'label' => 'Индекс пользователя',
                        'regex' => '^[0-9]{3,6}$',
                        'example' => '11300',
                        'type' => 'text'
                    ],
                    'phone' => [
                        'label' => 'Номер мобильного телефона пользователя',
                        'regex' => '^[0-9]{6,15}$',
                        'example' => '099123456',
                        'type' => 'text'
                    ],
                    'bank_code_id' => [
                        'label' => 'Код банка',
                        'regex' => '^[0-9]{1,2}$',
                        'example' => '5',
                        'type' => 'select',
                        'values' => [
                            '1' => 'Itaú',
                            '3' => 'Bradesco',
                            '4' => 'Banco do Brasil',
                            '5' => 'HSBC',
                            '6' => 'Santander',
                            '7' => 'BCP',
                            '8' => 'Inter bank',
                            '9' => 'BBVA',
                            '10' => 'Pago efectivo',
                            '11' => 'Bancomer',
                            '12' => 'Banamex',
                            '13' => 'Santander Mexico',
                            '14' => 'OXXO',
                            '15' => 'Redpagos',
                            '17' => 'Caixa',
                            '18' => 'Visa',
                            '19' => 'MasterCard',
                            '20' => 'Elo',
                            '21' => 'Diners Club',
                            '22' => 'Hipercard',
                            '23' => 'Cartao MercadoLivre',
                            '24' => 'American Express',
                            '25' => 'Visa Debit',
                            '26' => 'MasterCard Debit',
                            '28' => 'ePayLinks',
                            '29' => 'Credit Cards, Debit Cards and Online Bank Payments',
                            '30' => 'Banco de Chile',
                            '31' => 'Efecty',
                            '32' => 'Davivienda',
                            '33' => 'PSE (All banks)',
                            '34' => 'Santander Rio',
                            '35' => 'Pago Fácil',
                            '36' => 'Dinero Mail (Cash)',
                            '37' => 'RapiPago',
                            '38' => 'DineroMail (Transfer)',
                            '39' => 'Naranja',
                            '40' => 'Tarjeta Shopping',
                            '41' => 'Nativa',
                            '42' => 'Cencosud',
                            '43' => 'Cabal',
                            '44' => 'Argencard',
                            '45' => 'Halk Bank TL',
                            '46' => 'ING Bank TL',
                            '47' => 'Garanti Bankasi TL',
                            '48' => 'Is Bank TL',
                            '49' => 'VakifBank TL',
                            '50' => 'YapiKredi TL',
                            '51' => 'HSBC Bank TL',
                            '52' => 'Akbank TL',
                            '53' => 'Teb Bank',
                        ]
                    ]
                ],
                'd_hidden' => [
                    'term_url' => null,
                    'payment_type_id' => 45,
                    'action' => 'create_invoice'
                ],
                'w_hidden' => [
                    'action' => 'bank_account_payout'
                ]
            ],
            'withdraw' => true,
            'deposit' => true,
        ],
        'waw' => [
            'name' => 'Wire Acquiring Worldwide',
            'url' => 'bankAccount',
            'fields' => [
                'deposit' => [
                    'buyer_email' => [
                        'label' => 'Email пользователя',
                        'regex' => '',
                        'example' => 'customer@gmail.com',
                        'type' => 'email'
                    ],
                    'buyer_name' => [
                        'label' => 'Имя, фамилия пользователя',
                        'regex' => '^[a-zA-Z\s-]{3,256}$',
                        'example' => 'customer@gmail.com',
                        'type' => 'email'
                    ],
                    'bank_id' => [
                        'label' => 'Код банка',
                        'regex' => '^[A-Z]{2,4}$',
                        'example' => 'AK',
                        'type' => 'select',
                        'values' => [
                            'AK' => 'Akbank',
                            'ZB' => 'Ziraat Bankası',
                            'IS' => 'IS Bankası',
                            'DB' => 'Deniz Bank',
                            'GB' => 'Garanti Bankası',
                            'YKB' => 'Yapı Kredi Bankası',
                            'VB' => 'Vakıfbank',
                            'HSBC' => 'HSBC',
                            'HB' => 'Halk Bank',
                            'KT' => 'Kuveyt Turk',
                            'ING' => 'ING Bank',
                            'EP' => 'En Para',
                            'FB' => 'Finansbank',
                            'TEB' => 'Türkiye Ekonomi Bankası',
                        ]
                    ]
                ],
                'withdraw' => [
                    'bank_id' => [
                        'label' => 'Код банка',
                        'regex' => '^[A-Z]{2,4}$',
                        'example' => 'AK',
                        'type' => 'select',
                        'values' => [
                            'AK' => 'Akbank',
                            'ZB' => 'Ziraat Bankası',
                            'IS' => 'IS Bankası',
                            'DB' => 'Deniz Bank',
                            'GB' => 'Garanti Bankası',
                            'YKB' => 'Yapı Kredi Bankası',
                            'VB' => 'Vakıfbank',
                            'HSBC' => 'HSBC',
                            'HB' => 'Halk Bank',
                            'KT' => 'Kuveyt Turk',
                            'ING' => 'ING Bank',
                            'EP' => 'En Para',
                            'FB' => 'Finansbank',
                            'TEB' => 'Türkiye Ekonomi Bankası',
                        ],
                    ],
                    'bank_name' => [
                        'label' => 'Название банка',
                        'regex' => '^[a-zA-Z0-9\s-]{3,256}$',
                        'example' => '',
                        'type' => 'text',
                    ],
                    'bank_country' => [
                        'label' => 'Страна банка (ISO 3166-1 alpha-2)',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'RU, EN, DE, ...',
                        'type' => 'text',
                    ],
                    'bank_swift' => [
                        'label' => 'SWIFT (Society for Worldwide Interbank Financial Telecommunications)',
                        'regex' => '^[a-zA-Z0-9]{4,16}$',
                        'example' => '',
                        'type' => 'text',
                    ],
                    'bank_address' => [
                        'label' => 'Адрес банка',
                        'regex' => '^[a-zA-Z0-9\s-]{4,128}$',
                        'example' => '',
                        'type' => 'text',
                    ],
                    'bank_account' => [
                        'label' => 'IBAN (International Bank Account Number)',
                        'regex' => '^[a-zA-Z0-9\s-]{4,32}$',
                        'example' => '',
                        'type' => 'text',
                    ],
                    'owner_name' => [
                        'label' => 'Имя пользователя',
                        'regex' => '^[a-zA-Z0-9\s-]{3,128}$',
                        'example' => '',
                        'type' => 'text',
                    ],
                    'owner_address' => [
                        'label' => 'Адрес пользователя',
                        'regex' => '^[a-zA-Z0-9\s-]{3,128}$',
                        'example' => '',
                        'type' => 'text',
                    ],
                    'owner_country' => [
                        'label' => 'Код страны (ISO 3166-1 alpha-2)',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'RU, EN, DE, ...',
                        'type' => 'text',
                    ]
                ],
                'd_hidden' => [
                    'action' => 'create_invoice'
                ],
                'w_hidden' => [
                    'action' => 'payout'
                ]
            ],
            'withdraw' => true,
            'deposit' => true,
        ],
        'zimpler' => [
            'name' => 'Zimpler (Puggle Pay)',
            'url' => 'pugglePay',
            'fields' => [
                'deposit' => [
                    'phone' => [
                        'label' => 'Номер телефона',
                        'regex' => '',
                        'example' => 'Доступные коды: 46 - Швеция, 358 - Финляндия',
                        'type' => 'text',
                    ],
                    'email' => [
                        'label' => 'E-mail адрес пользователя',
                        'regex' => '',
                        'example' => 'IvanLolivier@gmail.com',
                        'type' => 'email',
                    ],
                    'country' => [
                        'label' => 'Страна проживания пользователя (согласно ISO 3166)',
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'BR',
                        'type' => 'text',
                    ]
                ],
                'd_hidden' => [
                    'payment_mode' => 1,
                    'action' => 'puggle_pay_init'
                ]
            ],
            'deposit' => true
        ],
        'epese' => [
            'name' => 'EPESE',
            'url' => 'epese',
            'fields' => [
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Идентификатор кошелька, на который совершается выплата',
                        'regex' => '',
                        'example' => '1234567',
                        'type' => 'text'
                    ],
                    'action' => [
                        'label' => 'Кошелек',
                        'regex' => '',
                        'example' => 'Z1234567890',
                        'type' => 'select',
                        'values' => [
                            'payout' => 'Выплата на внутренние кошельки EPESE',
                            'wmpayout' => 'Выплата на внутренние кошельки WebMoney'
                        ]
                    ]
                ]
            ],
            'deposit' => false,
            'withdraw' => true
        ],
        'paysec' => [
            'name' => 'PaySec',
            'url' => 'paySec',
            'fields' => [
                'withdraw' => [
                    'bank_code' => [
                        'label' => 'Код банка',
                        'regex' => '',
                        'example' => 'BAY',
                        'type' => 'select',
                        'values' => [
                            'ICBC' => 'Industrial and Commercial Bank of China',
                            'BOC' => 'Bank of China',
                            'BCOM' => 'Bank of Communication',
                            'CITIC' => 'China Citic Bank',
                            'CEB' => 'China Everbright Bank',
                            'SPDB' => 'Shanghai Pudong Development Bank',
                            'GDB' => 'Guangdong Development Bank',
                            'PAB' => 'Ping An Bank',
                            'CMB' => 'China Merchants Bank',
                            'HXB' => 'HuaXia Bank',
                            'ABC' => 'Agricultural Bank of China',
                            'CCB' => 'China Construction Bank',
                            'PSBC' => 'China Postal Savings Bank',
                            'CIB' => 'Industrial Bank',
                            '1' => 'SCB',
                            '2' => 'Krungthai',
                            '3' => 'Krungsri',
                            '4' => 'Bangkok Bank',
                            '5' => 'UOB',
                        ]
                    ],
                    'bank_branch' => [
                        'label' => 'Наименование филиала банка',
                        'regex' => '',
                        'example' => '中国农业银行清远市清和支行',
                        'type' => 'text',
                    ],
                    'bank_account_number' => [
                        'label' => 'Счет получателя',
                        'regex' => '^[0-9]{16}$',
                        'example' => '1234123412341234',
                        'type' => 'text',
                    ],
                    'bank_province' => [
                        'label' => 'Китайская провинция банка',
                        'regex' => '',
                        'example' => '北京',
                        'type' => 'text',
                    ],
                    'bank_city' => [
                        'label' => 'Город банка',
                        'regex' => '',
                        'example' => '北京',
                        'type' => 'text',
                    ]
                ],
                'w_hidden' => [
                    'action' => 'payout',
                    'site' => null
                ]
            ],
            'withdraw' => true
        ],
        'unionpay' => [
            'name' => 'China UnionPay',
            'url' => 'chinaBanks',
            'fields' => [
                'withdraw' => [
                    'bank_account_number' => [
                        'label' => 'Номер банковского аккаунта получателя',
                        'regex' => '^[0-9]{16}$',
                        'example' => '1234123412341234',
                        'type' => 'text',
                    ],
                    'customer_name' => [
                        'label' => 'Имя держателя банковского аккаунта',
                        'regex' => '',
                        'example' => 'Jian-Yang',
                        'type' => 'text',
                    ],
                    'bank_id' => [
                        'label' => 'Банк',
                        'regex' => '',
                        'example' => '',
                        'type' => 'select',
                        'values' => [
                            '1' => '中國工商銀行 (ICBC)',
                            '2' => '中國農業銀行 (ABOC)',
                            '3' => '中國銀行 (BOC)',
                            '4' => '中國建設銀行 (CCB)',
                            '5' => '交通銀行 (BOCM)',
                            '6' => '中國光大銀行 (CEB)',
                            '7' => '上海浦東發展銀行 (SPDB)',
                            '10' => '平安銀行 (PAB)',
                            '11' => '兴业银行 (CIB)',
                            '12' => '招商银行 (CMB)',
                            '14' => '中国邮政储蓄银行 (PSBC)',
                            '15' => '华夏银行 (HXB)',
                            '16' => '民生银行 (CMSB)',
                            '17' => '中信銀行 (ECITIC)',
                            '20' => '杭州銀行 (HZB)',
                        ]
                    ],
                    'bank_province' => [
                        'label' => 'Провинция, в которой располагается банк',
                        'regex' => '',
                        'example' => '',
                        'type' => 'text',
                    ],
                    'bank_area' => [
                        'label' => 'Регион, в котором располагается банк',
                        'regex' => '',
                        'example' => '',
                        'type' => 'text',
                    ],
                    'bank_branch' => [
                        'label' => 'Название филиала банка',
                        'regex' => '',
                        'example' => '',
                        'type' => 'text',
                    ],
                ],
                'w_hidden' => [
                    'action' => 'payout'
                ]
            ],
            'withdraw' => true
        ],
        'iwallet' => [
            'name' => 'iWallet',
            'url' => 'iWallet',
            'fields' => [
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Account ID, связанный с учетной записью пользователя',
                        'regex' => '',
                        'example' => '58479903',
                        'type' => 'text',
                    ],
                ],
                'w_hidden' => [
                    'action' => 'payout'
                ]
            ],
            'withdraw' => true
        ]
    ],
    'USD' => [
        'card' => [
            'name' => 'Card',
            'url' => 'card',
            'fields' => [
                'deposit' => [
                    'card' => [
                        'label' => 'Номер банковской карты Пользователя',
                        'regex' => '^[0-9]{16,19}$',
                        'example' => '1234123412341234',
                        'type' => 'text'
                    ],
                    'exp_month' => [
                        'label' => 'Месяц срока окончания действия карты',
                        'regex' => '^(0+[1-9])|(1+[0-2]))$',
                        'example' => '01',
                        'type' => 'text',
                    ],
                    'exp_year' => [
                        'label' => 'Год срока окончания действия карты',
                        'regex' => '^20+[0-9]{2}$',
                        'example' => '2018',
                        'type' => 'text',
                    ],
                    'cvv' => [
                        'label' => 'CVV код карты',
                        'regex' => '^[0-9]{3}$',
                        'example' => '123',
                        'type' => 'text',
                    ],
                    'holder' => [
                        'label' => 'Имя держателя (как на карте)',
                        'regex' => '^[a-zA-Z\s-]{4,256}$',
                        'example' => 'IVAN IVANOV',
                        'type' => 'text',
                    ]
                ],
                'w_hidden' => [
                    'action' => 'ym_payout'
                ]
            ],
            'deposit' => true
        ],
        'webmoney' => [
            'name' => 'WebMoney',
            'url' => 'webmoney',
            'fields' => [
                'deposit' => [
                    'customer_wmid' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => '123456789012',
                        'type' => 'text'
                    ]
                ],
                'd_hidden' => [
                    'protection_period' => 0,
                    'expiration_period' => 0,
                    'payment_type_id' => 10,
                    'action' => 'wmcreateinvoice'
                ],
                'w_hidden' => [
                    'action' => 'wmpayout'
                ],
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => 'R880329336884',
                        'type' => 'text'
                    ]
                ],
            ],
            'deposit' => true,
            'withdraw' => true
        ],
    ],
    'EUR' => [
        'card' => [
            'name' => 'Card',
            'url' => 'card',
            'fields' => [
                'deposit' => [
                    'card' => [
                        'label' => 'Номер банковской карты Пользователя',
                        'regex' => '^[0-9]{16,19}$',
                        'example' => '1234123412341234',
                        'type' => 'text'
                    ],
                    'exp_month' => [
                        'label' => 'Месяц срока окончания действия карты',
                        'regex' => '^(0+[1-9])|(1+[0-2]))$',
                        'example' => '01',
                        'type' => 'text',
                    ],
                    'exp_year' => [
                        'label' => 'Год срока окончания действия карты',
                        'regex' => '^20+[0-9]{2}$',
                        'example' => '2018',
                        'type' => 'text',
                    ],
                    'cvv' => [
                        'label' => 'CVV код карты',
                        'regex' => '^[0-9]{3}$',
                        'example' => '123',
                        'type' => 'text',
                    ],
                    'holder' => [
                        'label' => 'Имя держателя (как на карте)',
                        'regex' => '^[a-zA-Z\s-]{4,256}$',
                        'example' => 'IVAN IVANOV',
                        'type' => 'text',
                    ]
                ],
                'w_hidden' => [
                    'action' => 'ym_payout'
                ]
            ],
            'deposit' => true
        ],
        'webmoney' => [
            'name' => 'WebMoney',
            'url' => 'webmoney',
            'fields' => [
                'deposit' => [
                    'customer_wmid' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => '123456789012',
                        'type' => 'text'
                    ]
                ],
                'd_hidden' => [
                    'protection_period' => 0,
                    'expiration_period' => 0,
                    'payment_type_id' => 12,
                    'action' => 'wmcreateinvoice'
                ],
                'w_hidden' => [
                    'action' => 'wmpayout'
                ],
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => 'R880329336884',
                        'type' => 'text'
                    ]
                ],
            ],
            'deposit' => true,
            'withdraw' => true
        ],
    ],
    'UAH' => [
        'card' => [
            'name' => 'Card',
            'url' => 'card',
            'fields' => [
                'deposit' => [
                    'card' => [
                        'label' => 'Номер банковской карты Пользователя',
                        'regex' => '^[0-9]{16,19}$',
                        'example' => '1234123412341234',
                        'type' => 'text'
                    ],
                    'exp_month' => [
                        'label' => 'Месяц срока окончания действия карты',
                        'regex' => '^(0+[1-9])|(1+[0-2]))$',
                        'example' => '01',
                        'type' => 'text',
                    ],
                    'exp_year' => [
                        'label' => 'Год срока окончания действия карты',
                        'regex' => '^20+[0-9]{2}$',
                        'example' => '2018',
                        'type' => 'text',
                    ],
                    'cvv' => [
                        'label' => 'CVV код карты',
                        'regex' => '^[0-9]{3}$',
                        'example' => '123',
                        'type' => 'text',
                    ],
                    'holder' => [
                        'label' => 'Имя держателя (как на карте)',
                        'regex' => '^[a-zA-Z\s-]{4,256}$',
                        'example' => 'IVAN IVANOV',
                        'type' => 'text',
                    ]
                ],
                'w_hidden' => [
                    'action' => 'ym_payout'
                ]
            ],
            'deposit' => true
        ],
        'webmoney' => [
            'name' => 'WebMoney',
            'url' => 'webmoney',
            'fields' => [
                'deposit' => [
                    'customer_wmid' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => '123456789012',
                        'type' => 'text'
                    ]
                ],
                'd_hidden' => [
                    'protection_period' => 0,
                    'expiration_period' => 0,
                    'payment_type_id' => 13,
                    'action' => 'wmcreateinvoice'
                ],
                'w_hidden' => [
                    'action' => 'wmpayout'
                ],
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => 'R880329336884',
                        'type' => 'text'
                    ]
                ],
            ],
            'deposit' => true,
            'withdraw' => true
        ],
    ],
    'BYR' => [
        'card' => [
            'name' => 'Card',
            'url' => 'card',
            'fields' => [
                'deposit' => [
                    'card' => [
                        'label' => 'Номер банковской карты Пользователя',
                        'regex' => '^[0-9]{16,19}$',
                        'example' => '1234123412341234',
                        'type' => 'text'
                    ],
                    'exp_month' => [
                        'label' => 'Месяц срока окончания действия карты',
                        'regex' => '^(0+[1-9])|(1+[0-2]))$',
                        'example' => '01',
                        'type' => 'text',
                    ],
                    'exp_year' => [
                        'label' => 'Год срока окончания действия карты',
                        'regex' => '^20+[0-9]{2}$',
                        'example' => '2018',
                        'type' => 'text',
                    ],
                    'cvv' => [
                        'label' => 'CVV код карты',
                        'regex' => '^[0-9]{3}$',
                        'example' => '123',
                        'type' => 'text',
                    ],
                    'holder' => [
                        'label' => 'Имя держателя (как на карте)',
                        'regex' => '^[a-zA-Z\s-]{4,256}$',
                        'example' => 'IVAN IVANOV',
                        'type' => 'text',
                    ]
                ],
                'w_hidden' => [
                    'action' => 'ym_payout'
                ]
            ],
            'deposit' => true
        ],
        'webmoney' => [
            'name' => 'WebMoney',
            'url' => 'webmoney',
            'fields' => [
                'deposit' => [
                    'customer_wmid' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => '123456789012',
                        'type' => 'text'
                    ]
                ],
                'd_hidden' => [
                    'protection_period' => 0,
                    'expiration_period' => 0,
                    'payment_type_id' => 14,
                    'action' => 'wmcreateinvoice'
                ],
                'w_hidden' => [
                    'action' => 'wmpayout'
                ],
                'withdraw' => [
                    'customer_purse' => [
                        'label' => 'Уникальный идентификтор Пользователя',
                        'regex' => '',
                        'example' => 'R880329336884',
                        'type' => 'text'
                    ]
                ],
            ],
            'deposit' => true,
            'withdraw' => true
        ],
    ],
    'IDR' => [
        'paysec' => [
            'name' => 'PaySec',
            'url' => 'paySec',
            'fields' => [
                'withdraw' => [
                    'bank_code' => [
                        'label' => 'Код банка',
                        'regex' => '',
                        'example' => 'BAY',
                        'type' => 'select',
                        'values' => [
                            'ICBC' => 'Industrial and Commercial Bank of China',
                            'BOC' => 'Bank of China',
                            'BCOM' => 'Bank of Communication',
                            'CITIC' => 'China Citic Bank',
                            'CEB' => 'China Everbright Bank',
                            'SPDB' => 'Shanghai Pudong Development Bank',
                            'GDB' => 'Guangdong Development Bank',
                            'PAB' => 'Ping An Bank',
                            'CMB' => 'China Merchants Bank',
                            'HXB' => 'HuaXia Bank',
                            'ABC' => 'Agricultural Bank of China',
                            'CCB' => 'China Construction Bank',
                            'PSBC' => 'China Postal Savings Bank',
                            'CIB' => 'Industrial Bank',
                            '1' => 'SCB',
                            '2' => 'Krungthai',
                            '3' => 'Krungsri',
                            '4' => 'Bangkok Bank',
                            '5' => 'UOB',
                        ]
                    ],
                    'bank_branch' => [
                        'label' => 'Наименование филиала банка',
                        'regex' => '',
                        'example' => '中国农业银行清远市清和支行',
                        'type' => 'text',
                    ],
                    'bank_account_number' => [
                        'label' => 'Счет получателя',
                        'regex' => '^[0-9]{16}$',
                        'example' => '1234123412341234',
                        'type' => 'text',
                    ],
                    'bank_province' => [
                        'label' => 'Китайская провинция банка',
                        'regex' => '',
                        'example' => '北京',
                        'type' => 'text',
                    ],
                    'bank_city' => [
                        'label' => 'Город банка',
                        'regex' => '',
                        'example' => '北京',
                        'type' => 'text',
                    ]
                ],
                'w_hidden' => [
                    'action' => 'payout',
                    'site' => null
                ]
            ],
            'min' => 1.5,
            'max' => 13000000
        ],
    ],
    'CNY' => [
        'paysec' => [
            'name' => 'PaySec',
            'url' => 'paySec',
            'fields' => [
                'withdraw' => [
                    'bank_code' => [
                        'label' => 'Код банка',
                        'regex' => '',
                        'example' => 'BAY',
                        'type' => 'select',
                        'values' => [
                            'ICBC' => 'Industrial and Commercial Bank of China',
                            'BOC' => 'Bank of China',
                            'BCOM' => 'Bank of Communication',
                            'CITIC' => 'China Citic Bank',
                            'CEB' => 'China Everbright Bank',
                            'SPDB' => 'Shanghai Pudong Development Bank',
                            'GDB' => 'Guangdong Development Bank',
                            'PAB' => 'Ping An Bank',
                            'CMB' => 'China Merchants Bank',
                            'HXB' => 'HuaXia Bank',
                            'ABC' => 'Agricultural Bank of China',
                            'CCB' => 'China Construction Bank',
                            'PSBC' => 'China Postal Savings Bank',
                            'CIB' => 'Industrial Bank',
                            '1' => 'SCB',
                            '2' => 'Krungthai',
                            '3' => 'Krungsri',
                            '4' => 'Bangkok Bank',
                            '5' => 'UOB',
                        ]
                    ],
                    'bank_branch' => [
                        'label' => 'Наименование филиала банка',
                        'regex' => '',
                        'example' => '中国农业银行清远市清和支行',
                        'type' => 'text',
                    ],
                    'bank_account_number' => [
                        'label' => 'Счет получателя',
                        'regex' => '^[0-9]{16}$',
                        'example' => '1234123412341234',
                        'type' => 'text',
                    ],
                    'bank_province' => [
                        'label' => 'Китайская провинция банка',
                        'regex' => '',
                        'example' => '北京',
                        'type' => 'text',
                    ],
                    'bank_city' => [
                        'label' => 'Город банка',
                        'regex' => '',
                        'example' => '北京',
                        'type' => 'text',
                    ]
                ],
                'w_hidden' => [
                    'action' => 'payout',
                    'site' => null
                ]
            ],
            'min' => 10,
            'max' => 160000
        ],
    ],
    'THB' => [
        'paysec' => [
            'name' => 'PaySec',
            'url' => 'paySec',
            'fields' => [
                'withdraw' => [
                    'bank_code' => [
                        'label' => 'Код банка',
                        'regex' => '',
                        'example' => 'BAY',
                        'type' => 'select',
                        'values' => [
                            'ICBC' => 'Industrial and Commercial Bank of China',
                            'BOC' => 'Bank of China',
                            'BCOM' => 'Bank of Communication',
                            'CITIC' => 'China Citic Bank',
                            'CEB' => 'China Everbright Bank',
                            'SPDB' => 'Shanghai Pudong Development Bank',
                            'GDB' => 'Guangdong Development Bank',
                            'PAB' => 'Ping An Bank',
                            'CMB' => 'China Merchants Bank',
                            'HXB' => 'HuaXia Bank',
                            'ABC' => 'Agricultural Bank of China',
                            'CCB' => 'China Construction Bank',
                            'PSBC' => 'China Postal Savings Bank',
                            'CIB' => 'Industrial Bank',
                            '1' => 'SCB',
                            '2' => 'Krungthai',
                            '3' => 'Krungsri',
                            '4' => 'Bangkok Bank',
                            '5' => 'UOB',
                        ]
                    ],
                    'bank_branch' => [
                        'label' => 'Наименование филиала банка',
                        'regex' => '',
                        'example' => '中国农业银行清远市清和支行',
                        'type' => 'text',
                    ],
                    'bank_account_number' => [
                        'label' => 'Счет получателя',
                        'regex' => '^[0-9]{16}$',
                        'example' => '1234123412341234',
                        'type' => 'text',
                    ],
                    'bank_province' => [
                        'label' => 'Китайская провинция банка',
                        'regex' => '',
                        'example' => '北京',
                        'type' => 'text',
                    ],
                    'bank_city' => [
                        'label' => 'Город банка',
                        'regex' => '',
                        'example' => '北京',
                        'type' => 'text',
                    ]
                ],
                'w_hidden' => [
                    'action' => 'payout',
                    'site' => null
                ]
            ],
            'min' => null,
            'max' => 175000
        ],
    ]
];