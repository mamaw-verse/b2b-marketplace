<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispute>
 */
final class DisputeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'opened_by_user_id' => User::factory(),
            'type' => 'refund_only',
            'status' => 'open',
            'reason' => fake()->paragraph(),
        ];
    }

    /**
     * Indicate the dispute is a return & refund type awaiting return.
     */
    public function returnRefund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'return_refund',
            'status' => 'awaiting_return',
        ]);
    }

    /**
     * Indicate the dispute has been escalated.
     */
    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'escalated',
            'escalated_at' => now(),
        ]);
    }

    /**
     * Indicate the dispute was resolved with a full refund.
     */
    public function resolvedRefunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved_refunded',
            'resolved_at' => now(),
            'resolution_type' => 'full_refund',
            'resolution_amount' => fake()->randomFloat(2, 1000, 50000),
            'resolved_by_user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate the dispute was resolved as denied.
     */
    public function resolvedDenied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved_denied',
            'resolved_at' => now(),
            'resolution_type' => 'denied',
            'resolved_by_user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate the dispute was withdrawn.
     */
    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'withdrawn',
        ]);
    }
}
