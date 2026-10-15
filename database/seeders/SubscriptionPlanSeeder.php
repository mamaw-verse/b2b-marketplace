<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

final class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(
            ['name' => 'Annual Membership Plan'],
            [
                'country_scope' => null,
                'name' => 'Annual Membership Plan',
                'price' => '600.00',
                'currency' => 'PHP',
                'billing_period' => 'yearly',
                'trial_days' => config('marketplace.initial_subscription_trial_days', 180),
                'is_active' => true,
            ]
        );
    }
}
