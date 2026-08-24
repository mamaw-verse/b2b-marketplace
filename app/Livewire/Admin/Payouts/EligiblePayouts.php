<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Payouts;

use App\Actions\CreatePayoutBatchAction;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\PayoutSetting;
use App\Models\SellerPayoutAccount;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Eligible Payouts')]
final class EligiblePayouts extends Component
{
    #[Computed]
    public function eligibleSellers(): Collection
    {
        $disputeHoldDays = PayoutSetting::getDisputeHoldDays();
        $minimumThreshold = PayoutSetting::getMinimumPayoutThreshold();

        $accounts = SellerPayoutAccount::where('verification_status', 'verified')
            ->with('shop')
            ->get();

        $eligible = collect();

        foreach ($accounts as $account) {
            $eligibleOrders = Order::where('shop_id', $account->shop_id)
                ->where('status', 'completed')
                ->whereDoesntHave('payoutItem')
                ->where('completed_at', '<=', now()->subDays($disputeHoldDays))
                ->whereDoesntHave('disputes', function ($q) {
                    $q->whereIn('status', ['open', 'awaiting_return', 'return_received', 'escalated']);
                })
                ->pluck('id');

            if ($eligibleOrders->isEmpty()) {
                continue;
            }

            $totalReleasable = (float) LedgerEntry::whereIn('order_id', $eligibleOrders)
                ->where('type', 'payout')
                ->sum('amount');

            if ($totalReleasable < $minimumThreshold) {
                continue;
            }

            $eligible->push([
                'shop_id' => $account->shop_id,
                'shop_name' => $account->shop->storefront_name,
                'eligible_orders_count' => $eligibleOrders->count(),
                'releasable_amount' => $totalReleasable,
            ]);
        }

        return $eligible;
    }

    public function createBatch(int $shopId): void
    {
        try {
            $payout = (new CreatePayoutBatchAction)->execute($shopId);
            session()->flash('success', "Payout batch #{$payout->id} created successfully.");
            $this->redirectRoute('admin.payouts.show', $payout);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        return view('livewire.admin.payouts.eligible-payouts');
    }
}
