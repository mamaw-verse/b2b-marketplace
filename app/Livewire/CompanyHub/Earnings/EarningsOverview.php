<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Earnings;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Earnings')]
final class EarningsOverview extends Component
{
    use WithPagination;

    public string $dateFrom = '';

    public string $dateTo = '';

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function summary(): array
    {
        $shop = Auth::user()->shop;
        if (! $shop) {
            return ['total_earned' => '0.00', 'total_released' => '0.00', 'pending_balance' => '0.00', 'total_fees_paid' => '0.00'];
        }

        $shopId = $shop->id;

        return [
            'total_earned' => number_format($this->getPayoutItemsTotal($shopId), 2, '.', ''),
            'total_released' => number_format($this->getCompletedPayoutsTotal($shopId), 2, '.', ''),
            'pending_balance' => number_format($this->getPendingBalance($shopId), 2, '.', ''),
            'total_fees_paid' => number_format($this->getTotalFeesPaid($shopId), 2, '.', ''),
        ];
    }

    #[Computed]
    public function transactions(): CursorPaginator
    {
        $shop = Auth::user()->shop;
        if (! $shop) {
            return new \Illuminate\Pagination\CursorPaginator([], 15);
        }

        $query = DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.shop_id', $shop->id)
            ->select('ledger_entries.*');

        if ($this->dateFrom) {
            $query->where('ledger_entries.created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('ledger_entries.created_at', '<=', $this->dateTo.' 23:59:59');
        }

        $query->orderByDesc('ledger_entries.created_at');

        return $query->cursorPaginate(15);
    }

    public function render()
    {
        return view('livewire.company-hub.earnings.earnings-overview');
    }

    private function getPayoutItemsTotal(int $shopId): float
    {
        if (! Schema::hasTable('payout_items') || ! Schema::hasTable('payouts')) {
            return 0.00;
        }

        try {
            return (float) DB::table('payout_items')
                ->join('payouts', 'payouts.id', '=', 'payout_items.payout_id')
                ->where('payouts.shop_id', $shopId)
                ->where('payouts.status', 'completed')
                ->sum('payout_items.net_amount');
        } catch (Throwable) {
            return 0.00;
        }
    }

    private function getCompletedPayoutsTotal(int $shopId): float
    {
        if (! Schema::hasTable('payouts')) {
            return 0.00;
        }

        try {
            return (float) DB::table('payouts')
                ->where('shop_id', $shopId)
                ->where('status', 'completed')
                ->sum('net_amount');
        } catch (Throwable) {
            return 0.00;
        }
    }

    private function getPendingBalance(int $shopId): float
    {
        $query = DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.shop_id', $shopId)
            ->where('orders.status', 'completed')
            ->where('ledger_entries.type', 'escrow_hold');

        if (Schema::hasTable('payout_items')) {
            try {
                $query->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('payout_items')
                        ->whereColumn('payout_items.order_id', 'ledger_entries.order_id');
                });
            } catch (Throwable) {
                // Ignore if payout_items not available
            }
        }

        return (float) $query->sum('ledger_entries.amount');
    }

    private function getTotalFeesPaid(int $shopId): float
    {
        if (! Schema::hasTable('payout_items') || ! Schema::hasTable('payouts')) {
            return 0.00;
        }

        try {
            return (float) DB::table('payout_items')
                ->join('payouts', 'payouts.id', '=', 'payout_items.payout_id')
                ->where('payouts.shop_id', $shopId)
                ->where('payouts.status', 'completed')
                ->sum(DB::raw('payout_items.transaction_fee + payout_items.commission_fee'));
        } catch (Throwable) {
            return 0.00;
        }
    }
}
