<?php

return [
    'versions' => [
        'v0' => [
            'status' => 'deprecated',
            'deprecated_at' => '2025-01-01 23:59:59',
            'sunset_at' => '2025-11-09 23:59:59',
            'docs_url' => '',
            'message' => 'API v0 is deprecated and will be removed on 2025-06-30.',
        ],
        'v1' => [
            'status' => 'active',
            'deprecated_at' => null,
            'sunset_at' => null,
            'docs_url' => '',
            'message' => 'API v1 is active and stable.',
        ],
        'v2' => [
            'status' => 'recommended',
            'deprecated_at' => null,
            'sunset_at' => null,
            'docs_url' => '',
            'message' => 'API v2 is the latest recommended version.',
        ],
    ],
    'recommended_version' => 'v2',
];
