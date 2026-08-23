<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LedgerEntry;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
final class LedgerEntryFactory extends Factory
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
            'type' => 'escrow_hold',
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate the entry is an escrow hold.
     */
    public function escrowHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'escrow_hold',
            'note' => 'Escrow hold on payment',
        ]);
    }

    /**
     * Indicate the entry is a payout.
     */
    public function payout(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'payout',
            'note' => 'Payout on completion',
        ]);
    }

    /**
     * Indicate the entry is a refund.
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'refund',
            'note' => 'Refund issued',
        ]);
    }
}
