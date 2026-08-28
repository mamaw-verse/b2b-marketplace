<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\PayoutSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class ProfitCalculator extends Component
{
    public string $sale_price = '';

    public ?int $orderId = null;

    public ?float $prefillAmount = null;

    /**
     * @var array{net_amount: float, transaction_fee: float, commission_fee: float}|null
     */
    public ?array $actualFees = null;

    public function mount(?float $prefillAmount = null, ?int $orderId = null): void
    {
        $this->prefillAmount = $prefillAmount;
        $this->orderId = $orderId;

        if ($prefillAmount !== null && $prefillAmount > 0) {
            $this->sale_price = number_format($prefillAmount, 2, '.', '');
        }

        // If order has a payout_items row, show actual recorded fees
        if ($orderId && config('marketplace.features.payouts_enabled')) {
            $payoutItem = DB::table('payout_items')
                ->where('order_id', $orderId)
                ->first();

            if ($payoutItem) {
                $this->actualFees = [
                    'net_amount' => (float) $payoutItem->net_amount,
                    'transaction_fee' => (float) $payoutItem->transaction_fee,
                    'commission_fee' => (float) $payoutItem->commission_fee,
                ];
            }
        }
    }

    #[Computed]
    public function transactionFeeAmount(): float
    {
        return PayoutSetting::getTransactionFeeAmount();
    }

    #[Computed]
    public function commissionFeeRate(): float
    {
        return PayoutSetting::getCommissionFeeRate();
    }

    #[Computed]
    public function calculatedNet(): ?float
    {
        $price = (float) $this->sale_price;
        if ($price <= 0) {
            return null;
        }

        return round($price - $this->transactionFeeAmount - ($price * $this->commissionFeeRate), 2);
    }

    #[Computed]
    public function calculatedCommissionFee(): ?float
    {
        $price = (float) $this->sale_price;
        if ($price <= 0) {
            return null;
        }

        return round($price * $this->commissionFeeRate, 2);
    }

    public function render()
    {
        return view('livewire.company-hub.profit-calculator');
    }
}
