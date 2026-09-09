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
            'email' => null,
            'company_name' => null,
            'document_path' => null,
            'status' => 'interested',
            'addressed_by' => null,
            'addressed_at' => null,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes): array => [
            'shop_id' => null,
            'email' => $this->faker->safeEmail(),
            'company_name' => $this->faker->company(),
            'document_path' => 'certification-applications/2026/09/07/loi.docx',
        ]);
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'contacted',
            'addressed_at' => now(),
        ]);
    }
}
