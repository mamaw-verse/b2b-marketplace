<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
final class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reviewableType = fake()->randomElement([Product::class, Shop::class]);

        return [
            'order_id' => Order::factory()->completed(),
            'reviewer_user_id' => User::factory(),
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $reviewableType === Product::class
                ? Product::factory()
                : Shop::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->optional(0.7)->sentence(4),
            'body' => fake()->optional(0.8)->paragraph(),
            'is_hidden' => false,
        ];
    }

    /**
     * Review for a product.
     */
    public function forProduct(?Product $product = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewable_type' => Product::class,
            'reviewable_id' => $product?->id ?? Product::factory(),
        ]);
    }

    /**
     * Review for a shop.
     */
    public function forShop(?Shop $shop = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewable_type' => Shop::class,
            'reviewable_id' => $shop?->id ?? Shop::factory(),
        ]);
    }

    /**
     * A hidden review.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
