<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SellerPayoutAccount;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerPayoutAccount>
 */
final class SellerPayoutAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $banks = [
            'BDO' => 'BDO',
            'BPI' => 'BPI',
            'Metrobank' => 'MBTC',
            'Landbank' => 'LBP',
            'PNB' => 'PNB',
            'UnionBank' => 'UBP',
            'RCBC' => 'RCBC',
            'Security Bank' => 'SECB',
            'China Banking Corporation' => 'CBC',
            'EastWest Bank' => 'EWB',
        ];

        $bankName = fake()->randomElement(array_keys($banks));

        return [
            'shop_id' => Shop::factory(),
            'bank_name' => $bankName,
            'bank_code' => $banks[$bankName],
            'account_holder_name' => fake()->name(),
            'account_number' => fake()->regexify('[0-9]{10,16}'),
            'branch' => fake()->optional(0.7)->city().' Branch',
            'verification_status' => 'pending',
        ];
    }

    /**
     * Indicate the account is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    /**
     * Indicate the account is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
