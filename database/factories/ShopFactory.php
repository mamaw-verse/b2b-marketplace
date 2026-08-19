<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
final class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registered_business_name' => fake()->company(),
            'storefront_name' => fake()->company().' Store',
            'business_structure' => fake()->randomElement(['sole_proprietorship', 'partnership', 'corporation']),
            'dti_sec_number' => fake()->regexify('[A-Z]{2,3}-[0-9]{4}-[0-9]{5}'),
            'tax_identification_number' => fake()->regexify('[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address_1' => fake()->streetAddress(),
            'address_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'zip_code' => fake()->postcode(),
            'country_code' => 'PH',
            'fda_lto_number' => fake()->optional()->regexify('LTO-[0-9]{4}-[0-9]{6}'),
            'average_production_lead_time' => fake()->optional()->numberBetween(3, 60),
            'export_readiness_status' => fake()->randomElement(['never_exported', 'exporting_regionally', 'exporting_globally']),
            'value_chain_positions' => fake()->randomElements(
                ['farm', 'processing', 'manufacturing', 'distribution', 'retail', 'food_service'],
                fake()->numberBetween(1, 3)
            ),
            'capabilities' => fake()->randomElements(
                ['processing', 'contract_processing', 'packaging', 'contract_manufacturing', 'oem_odm', 'private_label', 'cold_storage', 'warehousing', 'distribution', 'wholesale', 'importing', 'exporting'],
                fake()->numberBetween(1, 4)
            ),
            'user_id' => User::factory(),
            'status' => 'active',
        ];
    }

    /**
     * Indicate the shop is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate the shop is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
