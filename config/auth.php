<?php

return [

    'defaults' => [
        'guard' => 'admin',
        'passwords' => 'admins',
    ],

    // ──────────────────────────────────────────────────────────────
    // GUARDS
    // In your original file, "who is logged in" was tracked by hand with
    // three parallel sets of $_SESSION keys (admin_*, teacher_*, student_*).
    // Laravel guards do the same job, but built-in: each guard has its own
    // session key and its own "provider" (which model + table to check).
    // ──────────────────────────────────────────────────────────────
    'guards' => [
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'teacher' => [
            'driver' => 'session',
            'provider' => 'teachers',
        ],
        'student' => [
            'driver' => 'session',
            'provider' => 'students',
        ],

        // Separate guard for YOU (the platform owner), distinct from any
        // school's admin guard. Never scoped by BelongsToSchool - platform
        // admins are meant to see across every tenant.
        'platform' => [
            'driver' => 'session',
            'provider' => 'platform_admins',
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // PROVIDERS
    // Tells each guard which Eloquent model represents that type of user.
    // ──────────────────────────────────────────────────────────────
    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
        'teachers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Teacher::class,
        ],
        'students' => [
            'driver' => 'eloquent',
            'model' => App\Models\Student::class,
        ],
        'platform_admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\PlatformAdmin::class,
        ],
    ],

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'teachers' => [
            'provider' => 'teachers',
            'table' => 'teacher_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'students' => [
            'provider' => 'students',
            'table' => 'student_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
