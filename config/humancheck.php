<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Human Check Enabled
    |--------------------------------------------------------------------------
    |
    | When disabled, all human checks are skipped. Always skipped in testing/local
    | unless explicitly enabled in tests or configuration.
    |
    */
    'enabled' => (bool) env('HUMANCHECK_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Human Pass Cookie Lifetime (in seconds)
    |--------------------------------------------------------------------------
    |
    | Default: 20 minutes (1200 seconds).
    |
    */
    'ttl' => (int) env('HUMANCHECK_TTL', 1200),

    /*
    |--------------------------------------------------------------------------
    | Bind Human Pass to IP Address
    |--------------------------------------------------------------------------
    |
    | When enabled, the pass is tied to sha256(ip). Disabled by default because
    | mobile networks change IP addresses frequently during a browsing session.
    |
    */
    'bind_ip' => (bool) env('HUMANCHECK_BIND_IP', false),

    /*
    |--------------------------------------------------------------------------
    | Low-risk Fail Open
    |--------------------------------------------------------------------------
    |
    | If verification errors or timeouts occur on low-risk routes, allow them
    | to pass when this is true. High-risk routes always fail closed.
    |
    */
    'low_risk_fail_open' => (bool) env('HUMANCHECK_LOW_RISK_FAIL_OPEN', false),

    /*
    |--------------------------------------------------------------------------
    | Expected Hostname & Action Verification
    |--------------------------------------------------------------------------
    |
    | Optionally verify the hostname and action returned by Cloudflare Turnstile.
    |
    */
    'expected_hostname' => env('TURNSTILE_EXPECTED_HOSTNAME'),
    'expected_action' => env('TURNSTILE_EXPECTED_ACTION'),
];
