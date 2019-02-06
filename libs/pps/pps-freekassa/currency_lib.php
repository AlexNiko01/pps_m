<?php

return [
    'RUB' => [
        'qiwi' => [
            'name' => 'Qiwi (156)',
            'currency_id' => 156,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'qiwi-2' => [
            'name' => 'Qiwi (128)',
            'currency_id' => 128,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => false,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'qiwi-wallet' => [
            'name' => 'Qiwi кошелек (155)',
            'currency_id' => 155,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'qiwi-wallet-2' => [
            'name' => 'QIWI кошелек (63)',
            'currency_id' => 63,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 4,
                'measurement' => 'percent',
            ],
        ],
        'yamoney' => [
            'name' => 'Яндекс.Деньги (45)',
            'id' => 45,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^41001\\d*$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1,
                'measurement' => 'percent',
            ],
        ],
        'card+' => [
            'name' => 'VISA/MASTERCARD+ (153)',
            'currency_id' => 153,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'card' => [
            'name' => 'VISA/MASTERCARD (94)',
            'currency_id' => 94,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер счета:',
                        'regex' => '^(?!404030|410653|413051|414939|414943|414949|414960|414961|414962|414963|417649|' .
                            '423396|424600|424657|432334|432335|432336|432337|432338|432339|432340|432575|' .
                            '434156|440129|440509|440535|440588|458120|458121|458122|462705|462708|473114|' .
                            '473117|473118|473121|476065|476339|513399|516798|516874|516875|516915|516933|' .
                            '516936|517691|521152|521153|521857|530217|532032|532957|535145|536354|544013|' .
                            '545708|545709|552324|557721|558335|558424|670509|676246)[0-9]{16}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 4,
                'measurement' => 'percent',
            ],
        ],
        'free-kassa' => [
            'name' => 'FK WALLET (133)',
            'currency_id' => 133,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^F[0-9]{7,10}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 0,
                'measurement' => 'percent',
            ],
        ],
        'ooopay' => [
            'name' => 'OOOPAY (106)',
            'currency_id' => 106,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => ''
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1,
                'measurement' => 'percent',
            ],
        ],
        'sberbank' => [
            'name' => 'Сбербанк (80)',
            'currency_id' => 80,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'alfa-bank' => [
            'name' => 'Альфа-банк (79)',
            'currency_id' => 79,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'okpay' => [
            'name' => 'OKPAY (60)',
            'currency_id' => 60,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^([O]{1}[K]{1}[\d]{9}|.*@.*)$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 2.5,
                'measurement' => 'percent',
            ],
        ],
        'payeer' => [
            'name' => 'PAYEER (114)',
            'currency_id' => 114,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^P[0-9]+$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 4.5,
                'measurement' => 'percent',
            ],
        ],
        'tks' => [
            'name' => 'Тинькофф Кредитные Системы (112)',
            'currency_id' => 112,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'promsvyazbank' => [
            'name' => 'Промсвязьбанк (110)',
            'currency_id' => 110,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'megafon' => [
            'name' => 'Мобильный Платеж Мегафон (82)',
            'currency_id' => 82,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'mts' => [
            'name' => 'Мобильный Платеж МТС (84)',
            'currency_id' => 84,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'tele2' => [
            'name' => 'Мобильный Платеж Tele2 (132)',
            'currency_id' => 132,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'beeline' => [
            'name' => 'Мобильный Платеж Билайн (83)',
            'currency_id' => 83,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'russian-terminals' => [
            'name' => 'Терминалы России (99)',
            'currency_id' => 99,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'comm-salons' => [
            'name' => 'Салоны связи (118)',
            'currency_id' => 118,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'western-union' => [
            'name' => 'Денежные переводы WU (117)',
            'currency_id' => 117,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'adv-cash' => [
            'name' => 'Advanced Cash (150)',
            'currency_id' => 150,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'pay-pal' => [
            'name' => 'PayPal (70)',
            'currency_id' => 70,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'E-mail:',
                        'regex' => ''
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 8.5,
                'measurement' => 'percent',
            ],
        ],
        'z-payment' => [
            'name' => 'Z-Payment (102)',
            'currency_id' => 102,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'megafon-nw' => [
            'name' => 'Мобильный Платеж МегаФон Северо-Западный филиал (137)',
            'currency_id' => 137,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'megafon-sib' => [
            'name' => 'Мобильный Платеж Мегафон Сибирский филиал (138)',
            'currency_id' => 138,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'megafon-cau' => [
            'name' => 'Мобильный Платеж Мегафон Кавказский филиал (139)',
            'currency_id' => 139,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'megafon-pov' => [
            'name' => 'Мобильный Платеж Мегафон Поволжский филиал (140)',
            'currency_id' => 140,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'megafon-ura' => [
            'name' => 'Мобильный Платеж Мегафон Уральский филиал (141)',
            'currency_id' => 141,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'megafon-fe' => [
            'name' => 'Мобильный Платеж Мегафон Дальневосточный филиал (142)',
            'currency_id' => 142,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'megafon-cen' => [
            'name' => 'Мобильный Платеж Мегафон Центральный филиал (143)',
            'currency_id' => 143,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер телефона:',
                        'regex' => '^[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1.5,
                'measurement' => 'percent',
            ],
        ],
        'webmoney' => [
            'name' => 'WebMoney (121)',
            'currency_id' => 121,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'webmoney-2' => [
            'name' => 'WebMoney VIP (105)',
            'currency_id' => 105,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^[RZ]\d{12}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'webmoney-3' => [
            'name' => 'WebMoney WMR (1)',
            'currency_id' => 1,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^R\d{12}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 4.5,
                'measurement' => 'percent',
            ],
        ],
        'webmoney-4' => [
            'name' => 'WebMoney WMR-bill (130)',
            'currency_id' => 130,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
    ],
    'USD' => [
        'ooopay' => [
            'name' => 'OOOPAY (87)',
            'currency_id' => 87,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => ''
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1,
                'measurement' => 'percent',
            ],
        ],
        'perfectmoney' => [
            'name' => 'Perfect Money (64)',
            'currency_id' => 64,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^[UE]\\d{6,}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 3.5,
                'measurement' => 'percent',
            ],
        ],
        'okpay' => [
            'name' => 'OKPAY (62)',
            'currency_id' => 62,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^([O]{1}[K]{1}[\d]{9}|.*@.*)$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 2.5,
                'measurement' => 'percent',
            ],
        ],
        'western-union' => [
            'name' => 'Денежные переводы WU (117)',
            'currency_id' => 117,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => ''
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'adv-cash' => [
            'name' => 'Advanced Cash (150)',
            'currency_id' => 150,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'z-payment' => [
            'name' => 'Z-Payment (102)',
            'currency_id' => 102,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'webmoney' => [
            'name' => 'WebMoney WMZ (2)',
            'currency_id' => 2,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^Z\d{12}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 5.5,
                'measurement' => 'percent',
            ],
        ],
        'webmoney-2' => [
            'name' => 'WebMoney WMZ-bill (131)',
            'currency_id' => 131,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^[RZ]\d{12}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
    ],
    'EUR' => [
        'card' => [
            'name' => 'VISA/MASTERCARD (124)',
            'currency_id' => 124,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'ooopay' => [
            'name' => 'OOOPAY (109)',
            'currency_id' => 109,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => ''
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 1,
                'measurement' => 'percent',
            ],
        ],
        'perfectmoney' => [
            'name' => 'Perfect Money (69)',
            'currency_id' => 69,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^[UE]\\d{6,}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 3.5,
                'measurement' => 'percent',
            ],
        ],
        'okpay' => [
            'name' => 'OKPAY (61)',
            'currency_id' => 61,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => '^([O]{1}[K]{1}[\d]{9}|.*@.*)$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => 2.5,
                'measurement' => 'percent',
            ],
        ],
        'western-union' => [
            'name' => 'Денежные переводы WU (117)',
            'currency_id' => 117,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер кошелька:',
                        'regex' => ''
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'adv-cash' => [
            'name' => 'Advanced Cash (150)',
            'currency_id' => 150,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'webmoney' => [
            'name' => 'WebMoney WME (3)',
            'currency_id' => 3,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
    ],
    'UAH' => [
        'card' => [
            'name' => 'VISA/MASTERCARD (67)',
            'currency_id' => 67,
            'fields' => [
                'deposit' => [],
                'withdraw' => [],
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'adv-cash' => [
            'name' => 'Advanced Cash (150)',
            'currency_id' => 150,
            'fields' => [
                'deposit' => [],
                'withdraw' => [],
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'visa' => [
            'name' => 'Visa (157)',
            'currency_id' => 157,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'purse' => [
                        'label' => 'Номер счета:',
                        'regex' => '^(?!404030|410653|413051|414939|414943|414949|414960|414961|414962|414963|417649|' .
                                       '423396|424600|424657|432334|432335|432336|432337|432338|432339|432340|432575|' .
                                       '434156|440129|440509|440535|440588|458120|458121|458122|462705|462708|473114|' .
                                       '473117|473118|473121|476065|476339|513399|516798|516874|516875|516915|516933|' .
                                       '516936|517691|521152|521153|521857|530217|532032|532957|535145|536354|544013|' .
                                       '545708|545709|552324|557721|558335|558424|670509|676246)[0-9]{16}$'
                    ]
                ],
            ],
            'deposit' => false,
            'withdraw' => true,
            'commission' => [
                'value' => 5,
                'measurement' => 'percent',
            ],
        ],
    ],
    'BTC' => [
        'bitcoin' => [
            'name' => 'Bitcoin (116)',
            'currency_id' => 116,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => 10,
                'measurement' => 'percent',
            ],
        ],
        'btc-e' => [
            'name' => 'BTC-E (146)',
            'currency_id' => 146,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'z-payment' => [
            'name' => 'Z-Payment (102)',
            'currency_id' => 102,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
    ],
    'LTC' => [
        'litecoin' => [
            'name' => 'Litecoin (147)',
            'currency_id' => 147,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => 0.0001,
                'measurement' => 'currency',
            ],
        ],
        'z-payment' => [
            'name' => 'Z-Payment (102)',
            'currency_id' => 102,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
    ],
    'ECN' => [
        'ecoin' => [
            'name' => 'eCoin (136)',
            'currency_id' => 136,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
        'z-payment' => [
            'name' => 'Z-Payment (102)',
            'currency_id' => 102,
            'fields' => [
                'deposit' => [],
                'withdraw' => []
            ],
            'deposit' => true,
            'withdraw' => false,
            'commission' => [
                'value' => null,
                'measurement' => null,
            ],
        ],
    ]
];