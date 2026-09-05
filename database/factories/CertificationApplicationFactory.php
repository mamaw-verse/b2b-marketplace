<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CertificationApplication;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificationApplication>
 */
final class CertificationApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'status' => 'interested',
            'addressed_by' => null,
            'addressed_at' => null,
        ];
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'contacted',
            'addressed_at' => now(),
        ]);
    }
}
