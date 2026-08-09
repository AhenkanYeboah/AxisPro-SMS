<?php

return [

    'paystack' => [
        // Public key is safe to expose client-side (it's what Paystack's
        // inline JS uses in the browser), so it's fine as a checked-in default.
        // Override via .env if you switch to a live key later.
        'public_key' => env('PAYSTACK_PUBLIC_KEY', 'pk_test_e7d2c45e0fbfe408a6a7cd79f633517490f6db90'),

        // Secret key is NEVER hardcoded here. Add it to your .env file as:
        //   PAYSTACK_SECRET_KEY=sk_test_xxxxxxxxxxxx
        // and it will be picked up automatically. If this is null, billing
        // routes will fail loudly rather than silently misbehaving - see
        // PaystackService::__construct().
        'secret_key' => env('PAYSTACK_SECRET_KEY'),

        // Paystack signs webhook payloads with your secret key using
        // HMAC-SHA512 in the x-paystack-signature header. We verify every
        // incoming webhook against this before trusting it - see
        // PaystackWebhookController.
        'webhook_url' => env('PAYSTACK_WEBHOOK_URL', '/paystack/webhook'),
    ],

    'arkesel' => [
        // Add to .env as: ARKESEL_API_KEY=xxxxxxxxxxxx
        // Never hardcoded here. If unset, SmsService::send() logs a
        // warning and no-ops rather than throwing, so a school without SMS
        // configured can still use email notices.
        'api_key' => env('ARKESEL_API_KEY'),

        // Arkesel requires a registered Sender ID (max 11 chars, no
        // spaces) before it will send anything - this needs setting up
        // with them ahead of time, and can take a few days to approve.
        // Add to .env as: ARKESEL_SENDER_ID=RCA
        'sender_id' => env('ARKESEL_SENDER_ID'),
    ],

    'anthropic' => [
        // Add to .env as: ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxx
        // Never hardcoded here. If unset, ClaudeClient throws loudly on
        // first use rather than silently returning ungrounded/empty
        // material - see ClaudeClient::__construct().
        'api_key' => env('ANTHROPIC_API_KEY'),

        // Model used for the research assistant. Configurable rather than
        // hardcoded in the service so it can be bumped without a code
        // change. Sonnet-tier is the right default for this feature: it's
        // reasoning over retrieved syllabus text and drafting teaching
        // material, not a latency-critical chat UI (which would favour
        // Haiku) or a task needing frontier reasoning depth (which would
        // favour Opus).
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),

        'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 4096),

        // Cost control: a teacher can otherwise hit "Generate" as many
        // times as they like, and every call spends real Anthropic API
        // cost with no visibility per school - given schools already pay
        // via Paystack subscriptions (see platform.billing.*), an
        // unmetered AI feature is a real cost leak, not just a nice-to-
        // have cap. This is deliberately a simple daily count per teacher
        // rather than a token-based budget - enough to stop runaway/
        // accidental repeated clicking without needing usage-based
        // billing infrastructure yet.
        'daily_limit_per_teacher' => env('RESEARCH_ASSISTANT_DAILY_LIMIT', 20),
    ],

    'zoom' => [
        // Server-to-Server OAuth app credentials from a free Zoom account
        // (Zoom Marketplace -> Build App -> Server-to-Server OAuth) - no
        // paid plan required for API access itself, though a free Zoom
        // account caps group meetings (3+ participants) at 40 minutes, so
        // this is genuinely fine for testing/small classes but a real
        // full-length lesson needs a paid Zoom plan on the connected
        // account eventually.
        //
        // Entirely optional: if unset, VirtualClassController simply
        // doesn't offer the "Create via Zoom" option and teachers use
        // Jitsi (zero setup, always available) or paste their own link
        // instead - see ZoomService::isConfigured().
        'account_id' => env('ZOOM_ACCOUNT_ID'),
        'client_id' => env('ZOOM_CLIENT_ID'),
        'client_secret' => env('ZOOM_CLIENT_SECRET'),
    ],

];
