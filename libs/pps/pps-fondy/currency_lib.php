<?php

$currencies = ['UAH', 'RUB', 'USD', 'EUR', 'GBR', 'CZK', 'BYR'];

$list = [];

foreach ($currencies as $currency) {
    $list[$currency] = [
        'fondy' => [
            'name' => 'Fondy',
            'fields' => [
                'deposit' => [],
                'withdraw' => [
                    'receiver_card_number' => [
                        'label' => 'Card number',
                        'regex' => '^[0-9]{13,19}$',
                    ],
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
        ],
    ];
}

return $list;