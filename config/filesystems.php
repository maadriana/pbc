<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // PBC Documents disk
        'pbc-documents' => [
            'driver' => 'local',
            'root' => storage_path('app/pbc-documents'),
            'url' => env('APP_URL').'/storage/pbc-documents',
            'visibility' => 'private', // Private for security
            'throw' => false,
        ],

        // For temporary files
        'temp' => [
            'driver' => 'local',
            'root' => storage_path('app/temp'),
            'throw' => false,
        ],

        // PBC Comments attachments
        'pbc-comments' => [
            'driver' => 'local',
            'root' => storage_path('app/pbc-comments'),
            'url' => env('APP_URL').'/storage/pbc-comments',
            'visibility' => 'private',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
