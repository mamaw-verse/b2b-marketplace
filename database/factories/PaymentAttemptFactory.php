<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderPaymentTerm;
use App\Models\PaymentAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentAttempt>
 */
final class PaymentAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_payment_term_id' => OrderPaymentTerm::factory(),
            'status' => 'pending',
            'dlt_merchant_transaction_id' => Str::limit(fake()->unique()->uuid(), 50, ''),
            'dlt_quick_link_token' => null,
            'dlt_payment_url' => null,
            'dlt_payment_status' => null,
            'dlt_amount' => null,
            'gateway_response' => null,
        ];
    }

    /**
     * Indicate the payment attempt has succeeded.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'succeeded',
            'succeeded_at' => now(),
            'dlt_payment_status' => 'PAID',
            'dlt_amount' => '71500.00',
        ]);
    }

    /**
     * Indicate the payment attempt has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failed_at' => now(),
            'dlt_payment_status' => 'REJECTED',
        ]);
    }
}
