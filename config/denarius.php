<?php

return [
    'admin' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'auth' => [
        'login' => [
            'max_attempts' => (int) env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
            'decay_seconds' => (int) env('AUTH_LOGIN_DECAY_SECONDS', 60),
        ],
        'registration' => [
            'max_attempts' => (int) env('AUTH_REGISTER_MAX_ATTEMPTS', 3),
            'decay_minutes' => (int) env('AUTH_REGISTER_DECAY_MINUTES', 1),
        ],
    ],

    'word_search' => [
        'rows' => 15,
        'columns' => 15,
        'word_count' => 10,
        'generation_attempts' => 20,
        'minimum_dimension' => 2,
        'maximum_dimension' => 50,
        'maximum_word_count' => 30,
        'alphabet' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    ],

    'scoring' => [
        'points_per_word' => 100,
        'completion_bonus' => 500,
        'speed_bonus_tiers' => [
            ['up_to_seconds' => 120, 'points' => 500],
            ['up_to_seconds' => 180, 'points' => 300],
            ['up_to_seconds' => 300, 'points' => 150],
        ],
    ],

    'game_interface' => [
        'decay_seconds' => 60,
        'start' => [
            'max_attempts' => 5,
        ],
        'selection' => [
            'max_attempts' => 120,
        ],
        'abandon' => [
            'max_attempts' => 5,
        ],
    ],
];
