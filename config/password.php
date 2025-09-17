<?php

return [
    'requirements' => [
        'min_length' => 8,
        'max_length' => 24,
        'complexity' => [
            'letters' => true,
            'mixed_case' => true,
            'numbers' => true,
            'symbols' => true,
            'uncompromised' => true,
        ],
        'forbidden_passwords' => [
            'password',
            'password123',
            'qwerty',
            '123456',
        ],
        'consecutive_characters_limit' => 3,
    ],

    'reset' => [
        'expire' => 60, // minutes
        'throttle' => 3, // max attempts per minute
    ],
];
