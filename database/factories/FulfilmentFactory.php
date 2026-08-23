<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Fulfilment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fulfilment>
 */
final class FulfilmentFactory extends Factory
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
            'status' => 'pending',
            'carrier' => null,
            'tracking_code' => null,
            'tracking_url' => null,
        ];
    }

    /**
     * Indicate the fulfilment has been shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'shipped_at' => now(),
            'carrier' => fake()->company(),
            'tracking_code' => fake()->bothify('??########'),
        ]);
    }

    /**
     * Indicate the fulfilment has been delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'shipped_at' => now()->subDays(3),
            'delivered_at' => now(),
        ]);
    }

    /**
     * Indicate the fulfilment has been returned to seller.
     */
    public function returnedToSeller(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'returned_to_seller',
            'shipped_at' => now()->subDays(3),
            'returned_at' => now(),
        ]);
    }
}
