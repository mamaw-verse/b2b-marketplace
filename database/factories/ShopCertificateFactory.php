<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use App\Models\Shop;
use App\Models\ShopCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopCertificate>
 */
final class ShopCertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'certification_body' => fake()->randomElement(['IDCP', 'HDIP', 'PRIME_CERTIFICATION', 'OTHER_NCMF']),
            'certification_body_other' => null,
            'registration_number' => fake()->regexify('[A-Z]{2,4}-[0-9]{4}-[0-9]{5}'),
            'expiry_date' => fake()->dateTimeBetween('+30 days', '+2 years'),
            'status' => 'active',
            'media_id' => Media::factory(),
            'is_verified' => true,
        ];
    }

    /**
     * Indicate the certificate is expiring soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => now()->addDays(fake()->numberBetween(1, 30)),
            'status' => 'expiring_soon',
        ]);
    }

    /**
     * Indicate the certificate has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => fake()->dateTimeBetween('-1 year', '-1 day'),
            'status' => 'expired',
        ]);
    }

    /**
     * Indicate the certificate is under admin review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
            'is_verified' => false,
        ]);
    }

    /**
     * Indicate the certificate has OTHER_NCMF body with a custom name.
     */
    public function otherBody(string $name = 'Custom Halal Board'): static
    {
        return $this->state(fn (array $attributes) => [
            'certification_body' => 'OTHER_NCMF',
            'certification_body_other' => $name,
        ]);
    }
}
