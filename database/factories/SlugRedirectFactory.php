<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Shop;
use App\Models\SlugRedirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlugRedirect>
 */
class SlugRedirectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sluggable_type' => fake()->randomElement([Product::class, Shop::class]),
            'sluggable_id' => fake()->numberBetween(1, 1000),
            'slug' => fake()->unique()->slug(),
        ];
    }
}
