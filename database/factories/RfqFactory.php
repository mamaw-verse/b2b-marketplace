<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BuyerProfile;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rfq>
 */
final class RfqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(6),
            'description' => fake()->optional()->paragraph(),
            'status' => 'open',
            'buyer_snapshot' => [
                'business_name' => fake()->company(),
                'billing_address' => [
                    'address_1' => fake()->streetAddress(),
                    'address_2' => null,
                    'city' => fake()->city(),
                    'province' => fake()->state(),
                    'zip_code' => fake()->postcode(),
                    'country_code' => 'PH',
                ],
                'shipping_address' => [
                    'address_1' => fake()->streetAddress(),
                    'address_2' => null,
                    'city' => fake()->city(),
                    'province' => fake()->state(),
                    'zip_code' => fake()->postcode(),
                    'country_code' => 'PH',
                ],
            ],
            'last_activity_at' => now(),
        ];
    }

    /**
     * Create an RFQ with a buyer snapshot from an actual BuyerProfile.
     */
    public function withBuyerProfile(BuyerProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $profile->user_id,
            'buyer_snapshot' => $profile->toSnapshot(),
        ]);
    }

    /**
     * Indicate the RFQ is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }
}
