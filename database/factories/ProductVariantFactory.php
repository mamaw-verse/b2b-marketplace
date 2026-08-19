<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
final class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->regexify('[A-Z]{2,3}-[0-9]{3,5}'),
            'description' => fake()->optional()->sentence(),
            'attributes' => [
                'size' => fake()->randomElement(['500g', '1kg', '5kg', '25kg']),
                'grade' => fake()->randomElement(['Food Grade A', 'Food Grade B', 'Industrial']),
            ],
            'price' => fake()->randomFloat(2, 10, 5000),
            'currency' => 'PHP',
            'minimum_order_quantity' => fake()->randomElement([1, 10, 25, 50, 100]),
            'quantity_breaks' => [
                ['min_qty' => 100, 'price' => fake()->randomFloat(2, 8, 4500)],
                ['min_qty' => 500, 'price' => fake()->randomFloat(2, 6, 4000)],
            ],
            'unit' => fake()->randomElement(['kg', 'bag', 'box', 'pallet', 'piece', 'carton']),
            'available_quantity' => fake()->optional()->numberBetween(100, 50000),
            'lead_time_days' => fake()->optional()->numberBetween(3, 45),
            'pack_size' => fake()->optional()->randomElement(['1kg pouch, 20 per carton', '500g bag', '25kg sack']),
            'status' => 'archived',
        ];
    }

    /**
     * Indicate the variant is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }
}
