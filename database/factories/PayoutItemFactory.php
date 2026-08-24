<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Payout;
use App\Models\PayoutItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayoutItem>
 */
final class PayoutItemFactory extends Factory
{
    protected $model = PayoutItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $escrowed = $this->faker->randomFloat(2, 500, 10000);
        $transactionFee = 15.00;
        $commissionFee = round($escrowed * 0.03, 2);
        $net = round($escrowed - $transactionFee - $commissionFee, 2);

        return [
            'payout_id' => Payout::factory(),
            'order_id' => Order::factory(),
            'ledger_entry_id' => LedgerEntry::factory(),
            'escrowed_amount' => $escrowed,
            'transaction_fee' => $transactionFee,
            'commission_fee' => $commissionFee,
            'net_amount' => $net,
        ];
    }
}
