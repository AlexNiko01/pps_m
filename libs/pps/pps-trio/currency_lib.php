<?php

return [
    'RUB' => [
        'w1' => [
            'code' => 'w1_rub',
            'paymethod_id' => 1,
            'name' => 'W1',
            'd_min' => 0.05,
            'd_max' => 100000,
            'w_min' => 0.01,
            'w_max' => 100000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Wallet number',
                        'regex' => '^\d{12}$',
                    ]
                ]
            ]
        ],
        'beeline' => [
            'code' => 'beeline_rub',
            'paymethod_id' => 2,
            'name' => 'Beeline',
            'd_min' => 10,
            'd_max' => 15000,
            'w_min' => 1,
            'w_max' => 15000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789'
                    ]
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Phone number',
                        'regex' => '^(79)[0-9]{9}$',
                        'example' => '79123456789'
                    ]
                ]
            ]
        ],
        'mts' => [
            'code' => 'mts_rub',
            'paymethod_id' => 3,
            'name' => 'MTS',
            'd_min' => 10,
            'd_max' => 14999,
            'w_min' => 1,
            'w_max' => 15000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789'
                    ]
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Phone number',
                        'regex' => '^(79)[0-9]{9}$',
                        'example' => '79123456789'
                    ]
                ]
            ]
        ],
        'megafon' => [
            'code' => 'megafon_rub',
            'paymethod_id' => 4,
            'name' => 'Megafon',
            'd_min' => 1,
            'd_max' => 14999,
            'w_min' => 1,
            'w_max' => 15000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789'
                    ]
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Phone number',
                        'regex' => '^(79)[0-9]{9}$',
                        'example' => '79123456789'
                    ]
                ]
            ]
        ],
        'tele2' => [
            'code' => 'tele2_rub',
            'paymethod_id' => 5,
            'name' => 'Tele2',
            'd_min' => 1,
            'd_max' => 5000,
            'w_min' => 1,
            'w_max' => 15000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789'
                    ]
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Phone number',
                        'regex' => '^(79)[0-9]{9}$',
                        'example' => '79123456789'
                    ]
                ]
            ]
        ],
        'card' => [
            'code' => 'card_rub',
            'paymethod_id' => 6,
            'name' => 'Банковские карты',
            'd_min' => 1,
            'd_max' => 60000,
            'w_min' => 50,
            'w_max' => 60000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [false],
                'withdraw' => [
                    'account' => [
                        'label' => 'Card number',
                        'regex' => '^\\d{16,18}$',
                    ]
                ]
            ]
        ],
        'dixis' => [
            'code' => 'dixis_rub',
            'paymethod_id' => 8,
            'name' => 'Dixis',
            'd_min' => 1,
            'd_max' => 100000,
            'deposit' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
            ]
        ],
        'okpay' => [
            'code' => 'okpay_rub',
            'paymethod_id' => 33,
            'name' => 'Okpay',
            'd_min' => 1,
            'd_max' => 100000,
            'w_min' => 0.01,
            'w_max' => 10000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Your purse',
                        'regex' => '^([O]{1}[K]{1}[\d]{9}|.*@.*)$',
                    ]
                ]
            ]
        ],
        'webmoney' => [
            'code' => 'webmoney_rub',
            'paymethod_id' => 34,
            'name' => 'Webmoney',
            'd_min' => 1,
            'd_max' => 100000,
            'w_min' => 0.01,
            'w_max' => 100000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        "regex" =>  "^[ZR]\\d{12}$",
                        "label" => "Wallet number"
                    ]
                ]
            ]
        ],
        'qiwi' => [
            'code' => 'qiwi_rub',
            'paymethod_id' => 36,
            'name' => 'Qiwi',
            'd_min' => 1,
            'd_max' => 15000,
            'w_min' => 1,
            'w_max' => 15000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    'phone' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '^(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$',
                        'example' => '79123456789'
                    ]
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '\\d{9,15}$',
                        'example' => '79123456789'
                    ]
                ]
            ]
        ],
        'yamoney' => [
            'code' => 'yamoney_rub',
            'paymethod_id' => 37,
            'name' => 'Yandex Money',
            'd_min' => 1,
            'd_max' => 100000,
            'w_min' => 1,
            'w_max' => 15000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'account' => [
                        'label' => 'Wallet number',
                        'regex' => '^41001\\d*$'
                    ]
                ]
            ]
        ],
        'payeer' => [
            'code' => 'payeer_rub',
            'paymethod_id' => 39,
            'name' => 'Payeer',
            'd_min' => 0.01,
            'd_max' => 500000,
            'w_min' => 0.02,
            'w_max' => 25000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'account' => [
                        'label' => 'Account',
                        'regex' => '^P[0-9]+$'
                    ]
                ]
            ]
        ],
        'adv-cash' => [
            'code' => 'advcash_rub',
            'paymethod_id' => 43,
            'name' => 'AdvCash',
            'w_min' => 10,
            'w_max' => 191967,
            'withdraw' => true,
            'fields' => [
                'withdraw' => [
                    'account' => [
                        'label' => 'Email',
                        'regex' => '^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$',
                        'example' => 'mail@example.com'
                    ]
                ]
            ]
        ],
    ],
    'USD' => [
        'w1' => [
            'code' => 'w1_usd',
            'paymethod_id' => 1,
            'name' => 'W1',
            'd_min' => 0.01,
            'd_max' => 10000,
            'w_min' => 0.01,
            'w_max' => 10000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Wallet number',
                        'regex' => '^\d{12}$',
                    ]
                ]
            ]
        ],
        'card' => [
            'code' => 'card_usd',
            'paymethod_id' => 6,
            'name' => 'Банковские карты',
            'd_min' => 1,
            'd_max' => 10000,
            'deposit' => true,
            'fields' => [
                'deposit' => [false],
            ]
        ],
        'webmoney' => [
            'code' => 'webmoney_usd',
            'paymethod_id' => 34,
            'name' => 'Webmoney',
            'd_min' => 0.02,
            'd_max' => 10000,
            'w_min' => 0.01,
            'w_max' => 10000,
            'deposit' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        "regex" =>  "^[ZR]\\d{12}$",
                        "label" => "Wallet number"
                    ]
                ]
            ]
        ],
        'wex' => [
            'code' => 'wex_usd',
            'paymethod_id' => 1,
            'name' => 'WEX',
            //'d_min' => 0.01,
            //'d_max' => 10000,
            'w_min' => 0.01,
            'w_max' => 10000,
            //'deposit' => true,
            'withdraw' => true,
            'fields' => [
                /*'deposit' => [
                    false
                ],*/
                'withdraw' => [
                    'account' => [
                        'label' => 'Email for getting WEX-Code',
                        'regex' => '^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$',
                        'example' => 'user@mail.com'
                    ]
                ]
            ]
        ],
        'perfectmoney' => [
            'code' => 'perfectmoney_usd',
            'paymethod_id' => 25,
            'name' => 'PerfectMoney',
            'd_min' => 1,
            'd_max' => 10000,
            'w_min' => 1,
            'w_max' => 10000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    "account" =>  [
                        "label" => "USD wallet number",
                        "regex" => "^[EU]\\d{6,}$",
                        'example' => 'U12345678'
                    ]
                ]
            ]
        ],
        'okpay' => [
            'code' => 'okpay_usd',
            'paymethod_id' => 33,
            'name' => 'Okpay',
            'd_min' => 0.01,
            'd_max' => 10000,
            'w_min' => 0.01,
            'w_max' => 10000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Your purse',
                        'regex' => '^([O]{1}[K]{1}[\d]{9}|.*@.*)$',
                    ]
                ]
            ]
        ],
        'qiwi' => [
            'code' => 'qiwi_usd',
            'paymethod_id' => 36,
            'name' => 'Qiwi',
            'd_min' => 0.5,
            'd_max' => 250,
            'w_min' => 1,
            'w_max' => 200,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    'phone' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '^(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$',
                        'example' => '79123456789'
                    ]
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '\d{9,15}$',
                        'example' => '79123456789'
                    ]
                ]
            ]
        ],
        'payeer' => [
            'code' => 'payeer_usd',
            'paymethod_id' => 39,
            'name' => 'Payeer',
            'd_min' => 0.01,
            'd_max' => 10000,
            'w_min' => 0.02,
            'w_max' => 30000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'account' => [
                        'label' => 'Account',
                        'regex' => '^P[0-9]+$'
                    ]
                ]
            ]
        ],
        'adv-cash' => [
            'code' => 'advcash_usd',
            'paymethod_id' => 43,
            'name' => 'AdvCash',
            'w_min' => 1,
            'w_max' => 3000,
            'withdraw' => true,
            'fields' => [
                'withdraw' => [
                    'account' => [
                        'label' => 'Email',
                        'regex' => '^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$',
                        'example' => 'mail@example.com'
                    ]
                ]
            ]
        ],
    ],
    'EUR' => [
        'w1' => [
            'code' => 'w1_eur',
            'paymethod_id' => 1,
            'name' => 'W1',
            'd_min' => 0.01,
            'd_max' => 10000,
            'w_min' => 0.01,
            'w_max' => 10000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Wallet number',
                        'regex' => '^\d{12}$',
                    ]
                ]
            ]
        ],
        'webmoney' => [
            'code' => 'webmoney_eur',
            'paymethod_id' => 34,
            'name' => 'Webmoney',
            'd_min' => 0.02,
            'd_max' => 10000,
            'w_min' => 0.01,
            'w_max' => 10000,
            'deposit' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
            ]
        ],
        'perfectmoney' => [
            'code' => 'perfectmoney_eur',
            'paymethod_id' => 25,
            'name' => 'PerfectMoney',
            'd_min' => 1,
            'd_max' => 10000,
            'w_min' => 1,
            'w_max' => 10000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    "account" =>  [
                        "label" => "EUR wallet number",
                        "regex" => "^[EU]\\d{6,}$",
                        'example' => 'U12345678'
                    ]
                ]
            ]
        ],
        'okpay' => [
            'code' => 'okpay_eur',
            'paymethod_id' => 33,
            'name' => 'Okpay',
            'd_min' => 0.01,
            'd_max' => 10000,
            'w_min' => 0.01,
            'w_max' => 10000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Your purse',
                        'regex' => '^([O]{1}[K]{1}[\d]{9}|.*@.*)$',
                    ]
                ]
            ]
        ],
        'qiwi' => [
            'code' => 'qiwi_eur',
            'paymethod_id' => 36,
            'name' => 'Qiwi',
            'd_min' => 0.5,
            'd_max' => 250,
            'w_min' => 1,
            'w_max' => 200,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    'phone' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '^(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$',
                        'example' => '79123456789'
                    ]
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '\d{9,15}$',
                        'example' => '79123456789'
                    ]
                ]
            ]
        ],
        'payeer' => [
            'code' => 'payeer_eur',
            'paymethod_id' => 39,
            'name' => 'Payeer',
            'd_min' => 0.01,
            'd_max' => 10000,
            'w_min' => 0.1,
            'w_max' => 100000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'account' => [
                        'label' => 'Account',
                        'regex' => '^P[0-9]+$'
                    ]
                ]
            ]
        ]
    ],
    'UAH' => [
        'w1' => [
            'code' => 'w1_uah',
            'paymethod_id' => 1,
            'name' => 'W1',
            'd_min' => 0.05,
            'd_max' => 30000,
            'w_min' => 0.01,
            'w_max' => 30000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Wallet number',
                        'regex' => '^\d{12}$',
                    ]
                ]
            ]
        ],
        'card' => [
            'code' => 'card_uah',
            'paymethod_id' => 6,
            'name' => 'Банковские карты',
            'd_min' => 1,
            'd_max' => 30000,
            'w_min' => 50,
            'w_max' => 10000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [false],
                'withdraw' => [
                    'account' => [
                        'label' => 'Card number',
                        'regex' => '^\\d{16,18}$'
                    ]
                ]
            ]
        ],
        'card-privat' => [
            'code' => 'card_privat_uah',
            'paymethod_id' => 6,
            'name' => 'Банковские карты ПриватБанк',
            'd_min' => 50,
            'd_max' => 10000,
            'w_min' => 50,
            'w_max' => 10000,
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
                'withdraw' => [
                    'account' => [
                        'label' => 'Card number of Privat Bank',
                        'regex' => '^(404030|410653|413051|414939|414943|414949|414960|414961|414962|414963|417649|423396|424600|424657|432334|432335|432336|432337|432338|432339|432340|432575|434156|440129|440509|440535|440588|458120|458121|458122|462705|462708|473114|473117|473118|473121|476065|476339|513399|516798|516874|516875|516915|516933|516936|517691|521152|521153|521857|530217|532032|532957|535145|536354|544013|545708|545709|552324|557721|558335|558424|670509|676246)[0-9]{10}$',
                    ]
                ]
            ]
        ],
        'privat24' => [
            'code' => 'privat24_uah',
            'paymethod_id' => 7,
            'name' => 'Приват24',
            'd_min' => 1,
            'd_max' => 30000,
            'deposit' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
            ]
        ],
        'webmoney' => [
            'code' => 'webmoney_uah',
            'paymethod_id' => 34,
            'name' => 'Webmoney',
            'd_min' => 1,
            'd_max' => 30000,
            'w_min' => 0.01,
            'w_max' => 30000,
            'deposit' => true,
            'fields' => [
                'deposit' => [
                    false
                ],
            ]
        ]
    ]
];