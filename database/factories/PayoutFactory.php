<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payout;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
final class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gross = $this->faker->randomFloat(2, 1000, 50000);
        $transactionFees = 15.00;
        $commissionFees = round($gross * 0.03, 2);
        $net = round($gross - $transactionFees - $commissionFees, 2);

        return [
            'shop_id' => Shop::factory(),
            'status' => 'pending',
            'currency' => 'PHP',
            'gross_amount' => $gross,
            'total_transaction_fees' => $transactionFees,
            'total_commission_fees' => $commissionFees,
            'net_amount' => $net,
            'orders_count' => 1,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => 'processing',
            'released_by_id' => User::factory(),
            'released_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'released_by_id' => User::factory(),
            'released_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'released_by_id' => User::factory(),
            'released_at' => now()->subMinutes(5),
            'failed_at' => now(),
            'failure_reason' => 'DLT Disbursement API returned an error.',
        ]);
    }
}
