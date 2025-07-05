<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Sync Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('DATABASE_SYNC_ENABLED', false),

    'endpoint' => env('DATABASE_SYNC_ENDPOINT', 'http://localhost:8080/sync-record'),

    'timeout' => env('DATABASE_SYNC_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Model Discovery
    |--------------------------------------------------------------------------
    */

    'model_paths' => [
        app_path('Models'),
        app_path(),
    ],

    // Explicitly define models to sync (optional)
    'models' => [
        // App\Models\User::class,
        // App\Models\Post::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    */

    // Only sync these tables (empty = all tables)
    'tables' => [
        // 'users',
        // 'posts',
    ],

    // Skip these tables
    'skip_tables' => [
        'migrations',
        'password_resets',
        'failed_jobs',
        'sync_queue',
        'sessions',
        'cache',
        'jobs',
    ],

    // Skip these fields
    'skip_fields' => [
        'password',
        'remember_token',
        'api_token',
        'email_verified_at',
    ],
];
