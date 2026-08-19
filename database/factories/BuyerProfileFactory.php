<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BuyerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuyerProfile>
 */
final class BuyerProfileFactory extends Factory
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
            'business_name' => fake()->company(),
            'billing_address_1' => fake()->streetAddress(),
            'billing_address_2' => fake()->optional()->secondaryAddress(),
            'billing_city' => fake()->city(),
            'billing_province' => fake()->state(),
            'billing_zip_code' => fake()->postcode(),
            'billing_country_code' => 'PH',
            'shipping_address_1' => fake()->streetAddress(),
            'shipping_address_2' => fake()->optional()->secondaryAddress(),
            'shipping_city' => fake()->city(),
            'shipping_province' => fake()->state(),
            'shipping_zip_code' => fake()->postcode(),
            'shipping_country_code' => 'PH',
            'contact_phone' => fake()->optional()->phoneNumber(),
            'contact_email' => fake()->optional()->companyEmail(),
        ];
    }
}
