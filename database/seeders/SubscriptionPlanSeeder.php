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
                // Approved: the first six months are waived in the Philippines only.
                'trial_days' => config('marketplace.instance_country') === 'PH' ? 180 : null,
                'is_active' => true,
            ]
        );
    }
}
