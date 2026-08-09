<?php

return [
    // How long a new school gets on a trial before needing to be on a paid
    // plan. Enforced by School::isActive() (see app/Models/School.php).
    'trial_days' => (int) env('SAAS_TRIAL_DAYS', 14),

    // Used only for DISPLAYING a school's full URL back to them after
    // signup (e.g. "yourschool.{base_domain}"). Doesn't affect how tenant
    // resolution actually works - that's driven by the real request host.
    // Update this once you're on real hosting with wildcard subdomain DNS.
    'base_domain' => env('SAAS_BASE_DOMAIN', 'yourproduct.com'),

    // Subdomains nobody is allowed to claim during signup, either because
    // they're needed for the platform itself (www, app, admin) or because
    // they'd be confusing/impersonation-prone (support, status, mail...).
    'reserved_subdomains' => [
        'www', 'app', 'admin', 'api', 'mail', 'ftp', 'cpanel', 'dashboard',
        'signup', 'login', 'static', 'cdn', 'assets', 'blog', 'support',
        'help', 'docs', 'status', 'ns1', 'ns2', 'mysql', 'phpmyadmin',
        'billing', 'platform',
    ],

    // Subscription plans a school can choose at checkout. Amounts are in
    // pesewas (GHS smallest unit) since that's what Paystack's API expects.
    // Keys here must match the `plan` string stored on schools/payments.
    // interval_months drives how far BillingController::markPaymentSuccessful()
    // pushes out subscription_ends_at on a successful payment - keep it in
    // sync with `interval` below (both currently "termly" / ~4 months).
    //
    // ⚠️ PRICING BELOW IS A PLACEHOLDER, NOT A RECOMMENDATION. GHS 15,000-
    // 40,000 per term is a significant sum - confirm these are genuinely the
    // numbers you want before taking a single real payment. Easiest way to
    // sanity-check: what would a school your size actually budget per term
    // for software like this?
    'plans' => [
        'basic' => [
            'name' => 'Basic',
            'amount_pesewas' => 15000 * 100, // GHS 15,000 / term - CONFIRM before launch
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Core student management: enrollment, attendance, report cards.',
        ],
        'standard' => [
            'name' => 'Standard',
            'amount_pesewas' => 25000 * 100, // GHS 25,000 / term - CONFIRM before launch
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Everything in Basic, plus assignments, timetables, and SMS notifications.',
        ],
        'premium' => [
            'name' => 'Premium',
            'amount_pesewas' => 40000 * 100, // GHS 40,000 / term - CONFIRM before launch
            'interval' => 'termly',
            'interval_months' => 4,
            'description' => 'Everything in Standard, plus priority support and custom branding.',
        ],
    ],
];
