<?php

return [
    'USD' => [
        'card' => [
            'name' => 'Astropay card',
            'deposit' => true,
            'withdraw' => true,
            'fields' => [
                'deposit' => [
                    'card_number' => [
                        'label' => "User's credit card number",
                        'regex' => '^[0-9]{16}$',
                        'example' => '4111111111111111'
                    ],
                    'exp_month' => [
                        'label' => "Credit card expiration month",
                        'regex' => '^((0[1-9])|(1[0-2]))$',
                        'example' => '08'
                    ],
                    'exp_year' => [
                        'label' => "Credit card expiration year",
                        'regex' => '^(20[1-3][0-9])$',
                        'example' => '2019'
                    ],
                    'cvv' => [
                        'label' => "Credit card expiration year",
                        'regex' => '^[0-9]{3,4}$',
                        'example' => '425'
                    ]
                ],
                'withdraw' => [
                    'document_id' => [
                        'label' => "Beneficiary's personal identification number",
                        'regex' => '',
                        'example' => '63017363201'
                    ],
                    'name' => [
                        'label' => "Beneficiary name or company",
                        'regex' => '^[a-zA-Z\s-]{2,40}$',
                        'example' => 'Carlos'
                    ],
                    'lastname' => [
                        'label' => "Beneficiary surname",
                        'regex' => '',
                        'example' => 'Bonavita'
                    ],
                    'country' => [
                        'label' => "User’s country. ISO 3166-1 alpha-2 code",
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'EN'
                    ],
                    'email' => [
                        'label' => "Email of the beneficiary",
                        'regex' => '',
                        'example' => 'my.email@mail.com'
                    ]
                ]
            ]
        ],
    ],
    'RUB' => [
        'card' => [
            'name' => 'Astropay card',
            'deposit' => true,
            'withdraw' => true,
            'min' => 0.01,
            'max' => 1000,
            'fields' => [
                'deposit' => [
                    'card_number' => [
                        'label' => "User's credit card number",
                        'regex' => '^[0-9]{16}$',
                        'example' => '4111111111111111'
                    ],
                    'exp_month' => [
                        'label' => "Credit card expiration month",
                        'regex' => '^((0[1-9])|(1[0-2]))$',
                        'example' => '08'
                    ],
                    'exp_year' => [
                        'label' => "Credit card expiration year",
                        'regex' => '^(20[1-3][0-9])$',
                        'example' => '2019'
                    ],
                    'cvv' => [
                        'label' => "Credit card expiration year",
                        'regex' => '^[0-9]{3,4}$',
                        'example' => '425'
                    ]
                ],
                'withdraw' => [
                    'document_id' => [
                        'label' => "Beneficiary's personal identification number",
                        'regex' => '',
                        'example' => '63017363201'
                    ],
                    'name' => [
                        'label' => "Beneficiary name or company",
                        'regex' => '^[a-zA-Z\s-]{2,40}$',
                        'example' => 'Carlos'
                    ],
                    'lastname' => [
                        'label' => "Beneficiary surname",
                        'regex' => '',
                        'example' => 'Bonavita'
                    ],
                    'country' => [
                        'label' => "User’s country. ISO 3166-1 alpha-2 code",
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'EN'
                    ],
                    'email' => [
                        'label' => "Email of the beneficiary",
                        'regex' => '',
                        'example' => 'my.email@mail.com'
                    ]
                ]
            ]
        ],
    ],
    'UAH' => [
        'card' => [
            'name' => 'Astropay card',
            'deposit' => true,
            'withdraw' => true,
            'min' => 0.01,
            'max' => 1000,
            'fields' => [
                'deposit' => [
                    'card_number' => [
                        'label' => "User's credit card number",
                        'regex' => '^[0-9]{16}$',
                        'example' => '4111111111111111'
                    ],
                    'exp_month' => [
                        'label' => "Credit card expiration month",
                        'regex' => '^((0[1-9])|(1[0-2]))$',
                        'example' => '08'
                    ],
                    'exp_year' => [
                        'label' => "Credit card expiration year",
                        'regex' => '^(20[1-3][0-9])$',
                        'example' => '2019'
                    ],
                    'cvv' => [
                        'label' => "Credit card expiration year",
                        'regex' => '^[0-9]{3,4}$',
                        'example' => '425'
                    ]
                ],
                'withdraw' => [
                    'document_id' => [
                        'label' => "Beneficiary's personal identification number",
                        'regex' => '',
                        'example' => '63017363201'
                    ],
                    'name' => [
                        'label' => "Beneficiary name or company",
                        'regex' => '^[a-zA-Z\s-]{2,40}$',
                        'example' => 'Carlos'
                    ],
                    'lastname' => [
                        'label' => "Beneficiary surname",
                        'regex' => '',
                        'example' => 'Bonavita'
                    ],
                    'country' => [
                        'label' => "User’s country. ISO 3166-1 alpha-2 code",
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'EN'
                    ],
                    'email' => [
                        'label' => "Email of the beneficiary",
                        'regex' => '',
                        'example' => 'my.email@mail.com'
                    ]
                ]
            ]
        ],
    ],
    'EUR' => [
        'card' => [
            'name' => 'Astropay card',
            'deposit' => true,
            'withdraw' => true,
            'min' => 0.01,
            'max' => 1000,
            'fields' => [
                'deposit' => [
                    'card_number' => [
                        'label' => "User's credit card number",
                        'regex' => '^[0-9]{16}$',
                        'example' => '4111111111111111'
                    ],
                    'exp_month' => [
                        'label' => "Credit card expiration month",
                        'regex' => '^((0[1-9])|(1[0-2]))$',
                        'example' => '08'
                    ],
                    'exp_year' => [
                        'label' => "Credit card expiration year",
                        'regex' => '^(20[1-3][0-9])$',
                        'example' => '2019'
                    ],
                    'cvv' => [
                        'label' => "Credit card expiration year",
                        'regex' => '^[0-9]{3,4}$',
                        'example' => '425'
                    ]
                ],
                'withdraw' => [
                    'document_id' => [
                        'label' => "Beneficiary's personal identification number",
                        'regex' => '',
                        'example' => '63017363201'
                    ],
                    'name' => [
                        'label' => "Beneficiary name or company",
                        'regex' => '^[a-zA-Z\s-]{2,40}$',
                        'example' => 'Carlos'
                    ],
                    'lastname' => [
                        'label' => "Beneficiary surname",
                        'regex' => '',
                        'example' => 'Bonavita'
                    ],
                    'country' => [
                        'label' => "User’s country. ISO 3166-1 alpha-2 code",
                        'regex' => '^[A-Z]{2}$',
                        'example' => 'EN'
                    ],
                    'email' => [
                        'label' => "Email of the beneficiary",
                        'regex' => '',
                        'example' => 'my.email@mail.com'
                    ]
                ]
            ]
        ],
    ],
];