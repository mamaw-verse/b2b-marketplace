<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
final class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 100);
        $unitPrice = fake()->randomFloat(2, 100, 10000);

        return [
            'order_id' => Order::factory(),
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['kg', 'sack', 'box', 'pcs', 'liter']),
            'unit_price' => $unitPrice,
            'subtotal' => round($quantity * $unitPrice, 2),
            'sort_order' => 0,
        ];
    }
}
