<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PayoutSetting;
use Illuminate\Database\Seeder;

final class PayoutSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'dispute_hold_days' => '7',
            'minimum_payout_threshold' => '500.00',
            'transaction_fee_amount' => '15.00',
            'commission_fee_rate' => '0.03',
        ];

        foreach ($defaults as $key => $value) {
            PayoutSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
