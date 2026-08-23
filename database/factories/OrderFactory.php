<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buyer_user_id' => User::factory(),
            'shop_id' => Shop::factory(),
            'quotation_id' => null,
            'status' => 'draft',
            'customer_business_name' => fake()->company(),
            'customer_contact_name' => fake()->name(),
            'customer_contact_phone' => fake()->phoneNumber(),
            'customer_contact_email' => fake()->safeEmail(),
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
            'currency' => 'PHP',
            'items_total' => fake()->randomFloat(2, 1000, 500000),
            'fees_total' => fake()->randomFloat(2, 0, 5000),
            'order_total' => fake()->randomFloat(2, 1000, 505000),
        ];
    }

    /**
     * Indicate the order is pending payment.
     */
    public function pendingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_payment',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate the order has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'submitted_at' => now()->subHours(2),
            'paid_at' => now(),
        ]);
    }

    /**
     * Indicate the order has been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'submitted_at' => now()->subDays(7),
            'paid_at' => now()->subDays(6),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate the order has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'submitted_at' => now()->subDays(2),
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Indicate the order has been refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'submitted_at' => now()->subDays(5),
            'paid_at' => now()->subDays(4),
            'cancelled_at' => now(),
        ]);
    }
}
