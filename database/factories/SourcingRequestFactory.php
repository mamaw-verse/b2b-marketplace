<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SourcingRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SourcingRequest>
 */
final class SourcingRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'product_name' => $this->faker->word(),
            'product_description' => $this->faker->sentence(),
            'reference_file_path' => null,
            'status' => 'pending',
            'notes' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'in_progress',
        ]);
    }
}
