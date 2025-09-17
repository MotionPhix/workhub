<?php

return [
    'csp' => [
        'development' => [
            'script_src' => [
                "'self'",
                "'unsafe-inline'",
                "'unsafe-eval'",
                'http://[::1]:5173',
                'ws://[::1]:5173',
            ],
            'style_src' => [
                "'self'",
                "'unsafe-inline'",
                'http://[::1]:5173',
            ],
        ],
        'production' => [
            'script_src' => [
                "'self'",
                "'strict-dynamic'",
            ],
            'style_src' => [
                "'self'",
                'https://fonts.bunny.net',
            ],
        ],
    ],
];
