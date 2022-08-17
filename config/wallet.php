<?php

return [
    'currencies' => [
        'EUR' => [
            'rate' => 1, // Default currency
            'decimals' => 2,
        ],
        'USD' => [
            'rate' => 1.129031, // Rate based in EUR
            'decimals' => 2,
        ],
        'JPY' => [
            'rate' => 130.869977, // Rate based in EUR
            'decimals' => 0,
        ],
    ],

    'fees' => [
        'deposit' => 0.03,
        'withdraw' => [
            'private' => [
                'free_amount' => 1000, //EUR, weekly
                'free_count' => 3, // weekly
                'commission' => 0.3,
            ],
            'business' => [
                'free_amount' => 0, // this is configurable because you may add free amount/count for businesses in the future
                'free_count' => 0,
                'commission' => 0.5,
            ],
        ],
    ],
];
