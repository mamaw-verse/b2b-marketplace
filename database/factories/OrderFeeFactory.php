<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderFee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderFee>
 */
final class OrderFeeFactory extends Factory
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
            'label' => fake()->randomElement(['Shipping', 'Packaging', 'Rush Fee', 'Insurance']),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'sort_order' => 0,
        ];
    }
}
