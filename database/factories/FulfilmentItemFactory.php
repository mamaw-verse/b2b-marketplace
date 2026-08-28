<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Fulfilment;
use App\Models\FulfilmentItem;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FulfilmentItem>
 */
final class FulfilmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fulfilment_id' => Fulfilment::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => fake()->randomFloat(2, 1, 1000),
        ];
    }
}
