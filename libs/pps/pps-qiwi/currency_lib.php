<?php

return [
    'RUB' => [
        'qiwi' => [
            'name' => 'Qiwi',
            'provider_id' => 99,
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
                    'phone' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '^\\d{9,15}$',
                        'example' => '79123456789'
                    ]
                ]
            ]
        ],
        'card' => [
            'name' => 'Qiwi card',
            'provider_id' => 31873,
            'deposit' => false,
            'withdraw' => true,
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'number' => [
                        'label' => 'Qiwi-card number',
                        'regex' => '^\\d{15,19}$',
                        'example' => '1111222233334444'
                    ]
                ]
            ]
        ]
    ],
];