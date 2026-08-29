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

    // FIXED PRICING: Your old 15k-40k per term is too high for Ghana basic schools.
    // Restored to realistic termly pricing you can launch with.
    // If you want to keep 15k/25k/40k, just change amount_pesewas back.
    'plans' => [
        'basic' => [
            'name' => 'Basic',
            'amount_pesewas' => 1500 * 100, // GHS 1,500 / term
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Core student management: enrollment, attendance, report cards.',
        ],
        'standard' => [
            'name' => 'Standard',
            'amount_pesewas' => 2500 * 100, // GHS 2,500 / term
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Everything in Basic, plus assignments, timetables, and SMS notifications.',
        ],
        'premium' => [
            'name' => 'Premium',
            'amount_pesewas' => 4000 * 100, // GHS 4,000 / term
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Everything in Standard, plus priority support and custom branding.',
        ],
        // If you want to keep your original 15k/25k/40k, uncomment below and comment above:
        /*
        'basic' => [
            'name' => 'Basic',
            'amount_pesewas' => 15000 * 100,
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Core student management: enrollment, attendance, report cards.',
        ],
        'standard' => [
            'name' => 'Standard',
            'amount_pesewas' => 25000 * 100,
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Everything in Basic, plus assignments, timetables, and SMS notifications.',
        ],
        'premium' => [
            'name' => 'Premium',
            'amount_pesewas' => 40000 * 100,
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Everything in Standard, plus priority support and custom branding.',
        ],
        */
    ],
];
