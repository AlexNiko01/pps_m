<?php

return [
    /*'RUB' => [
        $ps => [
            'code' => $ps,
            'name' => 'Russian ruble',
            'currency_code' => 'rur',
            'fields' => $fields,
            'deposit' => true,
            'withdraw' => false,
            'checkout' => false,
            'crypto' => false,
            'min' => 10,
            'commission' => [
                'name' => [
                    'Yandex Money' => 1,
                    'Bank card' => 3,
                    'Bank account' => 3,
                ],
                'measurement' => [
                    'Yandex Money' => 'percent',
                    'Bank card' => 'percent',
                    'Bank account' => 'percent',
                ],
            ],
        ],
    ],
    'USD' => [
        $ps => [
            'code' => $ps,
            'name' => 'United States dollar',
            'currency_code' => 'usd',
            'fields' => $fields,
            'deposit' => true,
            'withdraw' => false,
            'checkout' => false,
            'crypto' => false,
            'min' => 1,
            'commission' => [
                'name' => [],
                'measurement' => [],
            ],
        ],
    ],
    'UAH' => [
        $ps => [
            'code' => $ps,
            'name' => 'UAH',
            'currency_code' => 'uah',
            'fields' => $fields,
            'deposit' => true,
            'withdraw' => false,
            'checkout' => false,
            'crypto' => false,
            'min' => 1,
            'commission' => [
                'name' => [
                    'uah' => 1
                ],
                'measurement' => [
                    'uah' => 'uah'
                ],
            ],
        ],
    ],
    'EUR' => [
        $ps => [
            'code' => $ps,
            'name' => 'Euro',
            'currency_code' => 'eur',
            'fields' => $fields,
            'deposit' => true,
            'withdraw' => false,
            'checkout' => false,
            'crypto' => false,
            'min' => 1,
            'commission' => [
                'name' => [
                    'Bank account' => 1,
                ],
                'measurement' => [
                    'Bank account' => 'percent',
                ],
            ],
        ],
    ],*/
    'BLK' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Blackcoin',
            'currency_code' => 'blackcoin',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.1,
            'commission' => [
                'name' => [
                    'blackcoin' => 0.1,
                ],
                'measurement' => [
                    'blackcoin' => 'blackcoin',
                ],
            ],
        ],
    ],
    'BTC' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Bitcoin',
            'currency_code' => 'bitcoin',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.0001,
            'commission' => [
                'name' => [
                    'bitcoin' => 0.0001,
                ],
                'measurement' => [
                    'bitcoin' => 'bitcoin',
                ],
            ],
        ],
    ],
    'BCH' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Bitcoin Cash',
            'currency_code' => 'bitcoin_cash',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.0001,
            'commission' => [
                'name' => [
                    'bitcoin_cash' => 0.0001,
                ],
                'measurement' => [
                    'bitcoin_cash' => 'bitcoin_cash',
                ],
            ],
        ],
    ],
    'BCN' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Bytecoin',
            'currency_code' => 'bytecoin',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.0001,
            'commission' => [
                'name' => [
                    'bytecoin' => 0.0001,
                ],
                'measurement' => [
                    'bytecoin' => 'bytecoin',
                ],
            ],
        ],
    ],
    'XMR' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Monero',
            'currency_code' => 'monero',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.0001,
            'commission' => [
                'name' => [
                    'monero' => 0.0001,
                ],
                'measurement' => [
                    'monero' => 'monero',
                ],
            ],
        ],
    ],
    'XRP' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Ripple',
            'currency_code' => 'ripple',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.0001,
            'commission' => [
                'name' => [
                    'ripple' => 0.0001,
                ],
                'measurement' => [
                    'ripple' => 'ripple',
                ],
            ],
        ],
    ],
    'ZEC' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Zcash',
            'currency_code' => 'zcash',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.0001,
            'commission' => [
                'name' => [
                    'ripple' => 0.0001,
                ],
                'measurement' => [
                    'ripple' => 'ripple',
                ],
            ],
        ],
    ],
    'DASH' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Dash',
            'currency_code' => 'dash',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.005,
            'commission' => [
                'name' => [
                    'dash' => 0.001,
                ],
                'measurement' => [
                    'dash' => 'dash',
                ],
            ],
        ],
    ],
    'DOGE' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Dogecoin',
            'currency_code' => 'dogecoin',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 10,
            'commission' => [
                'name' => [
                    'dogecoin' => 1,
                ],
                'measurement' => [
                    'dogecoin' => 'dogecoin',
                ],
            ],
        ],
    ],
    'EMC' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Emercoin',
            'currency_code' => 'emercoin',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.01,
            'commission' => [
                'name' => [
                    'emercoin' => 0.1,
                ],
                'measurement' => [
                    'emercoin' => 'emercoin',
                ],
            ],
        ],
    ],
    'LTC' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Litecoin',
            'currency_code' => 'litecoin',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.005,
            'commission' => [
                'name' => [
                    'litecoin' => 0.001,
                ],
                'measurement' => [
                    'litecoin' => 'litecoin',
                ],
            ],
        ],
    ],
    'PPC' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Peercoin',
            'currency_code' => 'peercoin',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.1,
            'commission' => [
                'name' => [
                    'peercoin' => 0.01,
                ],
                'measurement' => [
                    'peercoin' => 'peercoin',
                ],
            ],
        ],
    ],
    'XPM' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Primecoin',
            'currency_code' => 'primecoin',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 0.1,
            'commission' => [
                'name' => [
                    'primecoin' => 0.1,
                ],
                'measurement' => [
                    'primecoin' => 'primecoin',
                ],
            ],
        ],
    ],
    'RDD' => [
        'cryptonator' => [
            'code' => 'cryptonator',
            'name' => 'Reddcoin',
            'currency_code' => 'reddcoin',
            'fields' => [],
            'deposit' => true,
            'withdraw' => false,
            'checkout' => true,
            'crypto' => true,
            'min' => 1000,
            'commission' => [
                'name' => [
                    'reddcoin' => 1,
                ],
                'measurement' => [
                    'reddcoin' => 'reddcoin',
                ],
            ],
        ],
    ],
];