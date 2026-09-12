<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderPaymentTerm;
use App\Models\PaymentAttempt;
use App\Models\Subscription;
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
            'sync_attempts' => 0,
            'next_sync_at' => null,
        ];
    }

    /**
     * Indicate the payment attempt belongs to a subscription (not an order term).
     */
    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscription->id,
            'order_payment_term_id' => null,
        ]);
    }

    /**
     * Indicate the payment attempt is pending with active sync polling.
     * Simulates an attempt that has been synced N times and is still unresolved.
     */
    public function syncPending(int $syncAttempts = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'dlt_payment_status' => 'PENDING',
            'sync_attempts' => $syncAttempts,
            'next_sync_at' => now()->addMinutes(5),
        ]);
    }

    /**
     * Indicate the payment attempt is a manual payment awaiting admin verification.
     */
    public function manualPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'manual',
            'status' => 'pending',
            'dlt_merchant_transaction_id' => null,
            'dlt_payment_status' => null,
            'manual_reference_number' => fake()->numerify('REF-########'),
            'manual_receipt_path' => 'subscription-receipts/1/'.fake()->uuid().'.jpg',
            'sync_attempts' => 0,
            'next_sync_at' => null,
        ]);
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
            'sync_attempts' => 0,
            'next_sync_at' => null,
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
            'sync_attempts' => 0,
            'next_sync_at' => null,
        ]);
    }
}
