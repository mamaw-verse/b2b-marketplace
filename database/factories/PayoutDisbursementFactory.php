<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payout;
use App\Models\PayoutDisbursement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PayoutDisbursement>
 */
final class PayoutDisbursementFactory extends Factory
{
    protected $model = PayoutDisbursement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payout_id' => Payout::factory(),
            'status' => 'pending',
            'amount' => $this->faker->randomFloat(2, 500, 50000),
            'attempted_at' => now(),
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => 'succeeded',
            'dlt_reference_id' => 'DLT-REF-'.Str::upper(Str::random(12)),
            'dlt_transaction_id' => 'TXN-'.Str::upper(Str::random(8)),
            'succeeded_at' => now(),
            'gateway_response' => ['status' => 'SUCCESS', 'message' => 'Disbursement completed'],
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'failed_at' => now(),
            'gateway_response' => ['status' => 'FAILED', 'message' => 'Insufficient funds'],
        ]);
    }
}
