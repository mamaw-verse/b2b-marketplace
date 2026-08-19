<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
final class QuotationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rfq_id' => Rfq::factory(),
            'rfq_recipient_id' => RfqRecipient::factory(),
            'status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'currency' => 'PHP',
            'total_amount' => fake()->randomFloat(2, 1000, 500000),
        ];
    }

    /**
     * Indicate the quotation has been submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
            'valid_until' => now()->addDays(14),
            'buyer_snapshot' => [
                'business_name' => fake()->company(),
                'billing_address' => [
                    'address_1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'province' => fake()->state(),
                    'zip_code' => fake()->postcode(),
                    'country_code' => 'PH',
                ],
                'shipping_address' => [
                    'address_1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'province' => fake()->state(),
                    'zip_code' => fake()->postcode(),
                    'country_code' => 'PH',
                ],
            ],
        ]);
    }

    /**
     * Indicate the quotation has been awarded.
     */
    public function awarded(): static
    {
        return $this->submitted()->state(fn (array $attributes) => [
            'status' => 'awarded',
        ]);
    }

    /**
     * Indicate the quotation has been rejected.
     */
    public function rejected(): static
    {
        return $this->submitted()->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate the quotation has been withdrawn.
     */
    public function withdrawn(): static
    {
        return $this->submitted()->state(fn (array $attributes) => [
            'status' => 'withdrawn',
        ]);
    }

    /**
     * Indicate the quotation has expired.
     */
    public function expired(): static
    {
        return $this->submitted()->state(fn (array $attributes) => [
            'status' => 'expired',
            'valid_until' => now()->subDay(),
        ]);
    }
}
