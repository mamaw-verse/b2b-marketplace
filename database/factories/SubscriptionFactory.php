<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
final class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'status' => 'pending_payment',
            'trial_ends_at' => null,
            'current_period_start' => null,
            'current_period_end' => null,
            'last_renewal_alert_threshold_days' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addMonths(11),
        ]);
    }

    public function trialing(): self
    {
        return $this->state(fn (): array => [
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(180),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(180),
        ]);
    }

    public function lapsed(): self
    {
        return $this->state(fn (): array => [
            'status' => 'lapsed',
            'current_period_start' => now()->subYear(),
            'current_period_end' => now()->subDay(),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (): array => [
            'status' => 'cancelled',
        ]);
    }
}
