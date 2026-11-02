<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Events\SubscriptionStatusChanged;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Give every active, approved seeded shop a trialing subscription so their
 * products are visible in the marketplace out-of-the-box.
 *
 * Each country runs its own app instance with a single global plan, so there
 * is no country-scoped plan selection here.
 */
final class DemoSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::whereNull('country_scope')->where('is_active', true)->first();

        if (! $plan) {
            $this->command->warn('DemoSubscriptionSeeder: no active subscription plan found — run SubscriptionPlanSeeder first.');

            return;
        }

        $trialDays = (int) $plan->trial_days;
        $shops = Shop::where('status', 'active')->get();
        $created = 0;

        foreach ($shops as $shop) {
            if ($shop->subscriptions()->exists()) {
                continue;
            }

            /** @var Subscription $subscription */
            $subscription = Subscription::create([
                'shop_id' => $shop->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'trialing',
                'trial_ends_at' => now()->addDays($trialDays),
                'current_period_start' => now(),
                'current_period_end' => now()->addDays($trialDays),
            ]);

            event(new SubscriptionStatusChanged($subscription));

            $created++;
        }

        $this->command->info("DemoSubscriptionSeeder: created {$created} demo subscription(s).");
    }
}
