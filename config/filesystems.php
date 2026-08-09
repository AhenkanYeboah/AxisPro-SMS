<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        // This is the disk our controllers use: ->store('uploads', 'public')
        // Files land in storage/app/public, and `php artisan storage:link`
        // makes them reachable at public/storage (i.e. the browser URL
        // /storage/uploads/xxx.jpg), which is what asset('storage/...') builds.
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
