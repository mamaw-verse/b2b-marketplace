<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderPaymentTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderPaymentTerm>
 */
final class OrderPaymentTermFactory extends Factory
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
            'type' => 'full',
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'due_at' => null,
        ];
    }
}
