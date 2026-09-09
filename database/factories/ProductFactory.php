<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => ucfirst(fake()->words(rand(2, 5), true)),
            'description' => fake()->optional()->paragraph(),
            'featured_image_path' => fake()->optional()->imageUrl(640, 480, 'food'),
            'status' => 'archived',
            'moderation_status' => 'active',
        ];
    }

    /**
     * Indicate the product is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    /**
     * Indicate the product is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    /**
     * Indicate the product's moderation status is flagged.
     */
    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => 'flagged',
        ]);
    }

    /**
     * Indicate the product has been admin-unpublished.
     */
    public function adminUnpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => 'admin_unpublished',
        ]);
    }
}
