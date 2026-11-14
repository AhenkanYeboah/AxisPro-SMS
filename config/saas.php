<?php

return [
    'trial_days' => (int) env('SAAS_TRIAL_DAYS', 14),

    // FIXED: env() takes only 2 args. Use Render URL as default.
    'base_domain' => env('SAAS_BASE_DOMAIN', 'axispro-sms.onrender.com'),

    'reserved_subdomains' => [
        'www', 'app', 'admin', 'api', 'mail', 'ftp', 'cpanel', 'dashboard',
        'signup', 'login', 'static', 'cdn', 'assets', 'blog', 'support',
        'help', 'docs', 'status', 'ns1', 'ns2', 'mysql', 'phpmyadmin',
        'billing', 'platform',
    ],

    // Termly subscription pricing (GHS). Amounts are stored in pesewas.
    // This is the single source of truth: signup, billing and checkout all read from here.
    'plans' => [
        'basic' => [
            'name' => 'Basic',
            'amount_pesewas' => 500 * 100, // GHS 500 / term
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Core student management: enrollment, attendance, report cards.',
            'features' => [
                'Student enrollment',
                'Attendance tracking',
                'Report cards',
            ],
        ],
        'standard' => [
            'name' => 'Standard',
            'amount_pesewas' => 700 * 100, // GHS 700 / term
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Everything in Basic, plus assignments, timetables, and SMS notifications.',
            'features' => [
                'Everything in Basic',
                'Assignments',
                'Timetables',
                'SMS notifications',
            ],
        ],
        'premium' => [
            'name' => 'Premium',
            'amount_pesewas' => 1000 * 100, // GHS 1,000 / term
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Everything in Standard, plus priority support and custom branding.',
            'features' => [
                'Everything in Standard',
                'Priority support',
                'Custom branding',
            ],
        ],
    ],
];
