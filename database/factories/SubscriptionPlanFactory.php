<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
final class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'country_scope' => null,
            'price' => '0.00',
            'currency' => 'PHP',
            'billing_period' => 'yearly',
            'trial_days' => null,
            'is_active' => true,
        ];
    }

    public function philippines(): self
    {
        return $this->state(fn (): array => [
            'country_scope' => 'PH',
            'trial_days' => 180,
        ]);
    }
}
