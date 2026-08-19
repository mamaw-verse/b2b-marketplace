<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationItem>
 */
final class QuotationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 1000);
        $unitPrice = fake()->randomFloat(2, 10, 10000);

        return [
            'quotation_id' => Quotation::factory(),
            'description' => fake()->sentence(4),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['kg', 'pcs', 'box', 'sack', 'pallet', 'litre', 'bag']),
            'unit_price' => $unitPrice,
            'subtotal' => round($quantity * $unitPrice, 2),
            'sort_order' => 0,
        ];
    }
}
