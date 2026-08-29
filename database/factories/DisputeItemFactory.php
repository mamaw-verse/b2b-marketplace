<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\DisputeItem;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisputeItem>
 */
final class DisputeItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dispute_id' => Dispute::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
