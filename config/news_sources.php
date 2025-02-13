<?php

return [
    'new_york_times' => [
        'rate_limit' => [
            'max_requests' => 5,
            'per_minutes' => 1,
        ],
        'api_key' => env('NYTIMES_API_KEY'),
    ],
    'newsapi' => [
        'rate_limit' => [
            'max_requests' => 5,
            'per_minutes' => 1,
        ],
        'api_key' => env('NEWSAPI_API_KEY'),
    ],
    'guardian' => [
        'rate_limit' => [
            'max_requests' => 5,
            'per_minutes' => 1,
        ],
        'api_key' => env('GUARDIAN_API_KEY'),
    ]
];