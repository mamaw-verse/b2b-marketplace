<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductShareStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductShareStat> */
final class ProductShareStatFactory extends Factory
{
    protected $model = ProductShareStat::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        /** @var list<string> $channels */
        $channels = config('marketplace.share.channels', ['copy', 'facebook', 'messenger', 'x', 'native']);

        return [
            'product_id' => Product::factory(),
            'channel' => fake()->randomElement($channels),
            'shared_on' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'count' => fake()->numberBetween(1, 100),
        ];
    }
}
