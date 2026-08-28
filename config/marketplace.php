<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Commission Rate
    |--------------------------------------------------------------------------
    |
    | Flat commission rate applied to completed orders for platform revenue.
    | 0.01 = 1%. Used in payout calculations and reports.
    |
    */
    'commission_rate' => (float) env('MARKETPLACE_COMMISSION_RATE', 0.01),

    /*
    |--------------------------------------------------------------------------
    | Auto-Accept Days
    |--------------------------------------------------------------------------
    |
    | Number of days after delivery before the system auto-confirms receipt
    | if the buyer takes no action (and no dispute is open).
    |
    */
    'auto_accept_days' => (int) env('MARKETPLACE_AUTO_ACCEPT_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags to enable/disable optional functionality.
    |
    */
    'features' => [
        /*
         | Payouts feature (Phase 4). Set to true once payout migrations are
         | finalized. Replaces Schema::hasTable anti-pattern.
         */
        'payouts_enabled' => (bool) env('MARKETPLACE_PAYOUTS_ENABLED', true),
    ],

];
