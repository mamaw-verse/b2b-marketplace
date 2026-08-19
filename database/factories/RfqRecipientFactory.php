<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RfqRecipient>
 */
final class RfqRecipientFactory extends Factory
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
            'shop_id' => Shop::factory(),
            'status' => 'received',
            'last_activity_at' => now(),
        ];
    }

    /**
     * Indicate the recipient has reviewed the RFQ.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewed',
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate the recipient is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate the recipient has declined (no bid).
     */
    public function noBid(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'no_bid',
            'decline_reason' => $reason ?? fake()->optional()->sentence(),
        ]);
    }
}
