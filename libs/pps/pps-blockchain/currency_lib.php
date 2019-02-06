<?php

return [
    'BTC' => [
        'bitcoin' => [
            'name' => 'Bitcoin',
            'fields' => [
                'withdraw' => [
                    'to' => [
                        'label' => 'Address',
                        'regex' => '^[0-9a-zA-Z]{32,34}$',
                        'example' => '19jJyiC6DnKyKvPg38eBE8R6yCSXLLEjqw',
                        'type' => 'text'
                    ]
                ]
            ],
            'deposit' => true,
            'withdraw' => true,
            'min' => 0.00015,
            'max' => 0.1
        ]
    ]
];