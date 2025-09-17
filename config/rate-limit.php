<?php

return [
    'limits' => [
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 60,
        ],
        'registration' => [
            'max_attempts' => 3,
            'decay_minutes' => 60,
        ],
        'password_reset' => [
            'max_attempts' => 3,
            'decay_minutes' => 60,
        ],
        'api' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],
];
