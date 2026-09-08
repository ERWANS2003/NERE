<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vercel Serverless Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration helps optimize Laravel for Vercel's serverless
    | environment with proper caching and session handling.
    |
    */

    'cache_prefix' => env('CACHE_PREFIX', 'vercel_'),
    
    'storage_path' => env('STORAGE_PATH', '/tmp'),
    
    'view_compiled_path' => env('VIEW_COMPILED_PATH', '/tmp/views'),
    
    'session' => [
        'driver' => env('SESSION_DRIVER', 'cookie'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => env('SESSION_ENCRYPT', true),
        'files' => '/tmp/sessions',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => env('SESSION_COOKIE', 'laravel_session'),
        'path' => '/',
        'domain' => env('SESSION_DOMAIN', null),
        'secure' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => true,
        'same_site' => 'lax',
    ],
    
    'cache' => [
        'default' => env('CACHE_DRIVER', 'array'),
        'stores' => [
            'array' => [
                'driver' => 'array',
                'serialize' => false,
            ],
            'file' => [
                'driver' => 'file',
                'path' => '/tmp/cache',
            ],
        ],
        'prefix' => env('CACHE_PREFIX', 'vercel_'),
    ],
];