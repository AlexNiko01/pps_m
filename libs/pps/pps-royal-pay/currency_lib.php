<?php

return [
    'RUB' => [
        'qiwi' => [
            'code' => 'Qiwi',
            'name' => 'Visa Qiwi wallet',
            'fields' => [
                'deposit' => [
                    'qiwi_wallet' => [
                        'label' => 'Номер Qiwi кошелька клиента',
                        'regex' => '^\+(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$'
                    ]
                ],
                'hidden' => [
                    'qiwi_comment' => 'comment',
                    'qiwi_lifetime' => 'lifetime',
                    'qiwi_successUrl' => 'success_url',
                    'qiwi_failUrl' => 'fail_url'
                ],
                'withdraw' => [
                    'qiwi_wallet' => [
                        'label' => 'Номер Qiwi кошелька клиента',
                        'regex' => '^\+(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true
        ],
        'adv-cash' => [
            'code' => 'Advcash',
            'name' => 'Advanced Cash',
            'fields' => [
                'hidden' => [
                    'ac_description' => 'comment',
                    'ac_success_url' => 'success_url',
                    'ac_success_url_method' => 'success_url_method',
                    'ac_fail_url' => 'fail_url',
                    'ac_fail_url_method' => 'fail_url_method'
                ],
                'withdraw' => [
                    'ac_wallet' => [
                        'label' => 'Номер счета клиента',
                        'regex' => '^[0-9]{16}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true
        ]
    ],
    'USD' => [
        'adv-cash' => [
            'code' => 'Advcash',
            'name' => 'Advanced Cash',
            'fields' => [
                'hidden' => [
                    'ac_description' => 'comment',
                    'ac_success_url' => 'success_url',
                    'ac_success_url_method' => 'success_url_method',
                    'ac_fail_url' => 'fail_url',
                    'ac_fail_url_method' => 'fail_url_method'

                ],
                'withdraw' => [
                    'ac_wallet' => [
                        'label' => 'Номер счета клиента',
                        'regex' => '^[0-9]{16}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true
        ]
    ],
    'EUR' => [
        'adv-cash' => [
            'code' => 'Advcash',
            'name' => 'Advanced Cash',
            'fields' => [
                'hidden' => [
                    'ac_description' => 'comment',
                    'ac_success_url' => 'success_url',
                    'ac_success_url_method' => 'success_url_method',
                    'ac_fail_url' => 'fail_url',
                    'ac_fail_url_method' => 'fail_url_method'

                ],
                'withdraw' => [
                    'ac_wallet' => [
                        'label' => 'Номер счета клиента',
                        'regex' => '^[0-9]{16}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true
        ]
    ],
    'UAH' => [
        'adv-cash' => [
            'code' => 'Advcash',
            'name' => 'Advanced Cash',
            'fields' => [
                'hidden' => [
                    'ac_description' => 'comment',
                    'ac_success_url' => 'success_url',
                    'ac_success_url_method' => 'success_url_method',
                    'ac_fail_url' => 'fail_url',
                    'ac_fail_url_method' => 'fail_url_method'

                ]
            ],
            'deposit' => true,
            'withdraw' => false
        ]
    ],
    'GBP' => [
        'adv-cash' => [
            'code' => 'Advcash',
            'name' => 'Advanced Cash',
            'fields' => [
                'hidden' => [
                    'ac_description' => 'comment',
                    'ac_success_url' => 'success_url',
                    'ac_success_url_method' => 'success_url_method',
                    'ac_fail_url' => 'fail_url',
                    'ac_fail_url_method' => 'fail_url_method'
                ],
                'withdraw' => [
                    'ac_wallet' => [
                        'label' => 'Номер счета клиента',
                        'regex' => '^[0-9]{16}$'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true
        ]
    ]
];