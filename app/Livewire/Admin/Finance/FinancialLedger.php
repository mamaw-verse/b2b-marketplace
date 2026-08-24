<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Financial Ledger')]
final class FinancialLedger extends Component
{
    use WithPagination;

    public string $type = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $shopId = '';

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedShopId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function totals(): array
    {
        $query = DB::table('ledger_entries');

        return [
            'escrow_hold' => number_format((float) (clone $query)->where('type', 'escrow_hold')->sum('amount'), 2, '.', ''),
            'payout' => number_format((float) (clone $query)->where('type', 'payout')->sum('amount'), 2, '.', ''),
            'refund' => number_format((float) (clone $query)->where('type', 'refund')->sum('amount'), 2, '.', ''),
        ];
    }

    #[Computed]
    public function entries(): CursorPaginator
    {
        $query = DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->leftJoin('shops', 'shops.id', '=', 'orders.shop_id')
            ->select([
                'ledger_entries.*',
                'orders.shop_id',
                'orders.buyer_user_id',
                'orders.status as order_status',
                'shops.storefront_name as shop_name',
            ]);

        if ($this->type) {
            $query->where('ledger_entries.type', $this->type);
        }

        if ($this->shopId) {
            $query->where('orders.shop_id', (int) $this->shopId);
        }

        if ($this->dateFrom) {
            $query->where('ledger_entries.created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('ledger_entries.created_at', '<=', $this->dateTo.' 23:59:59');
        }

        $query->orderByDesc('ledger_entries.created_at');

        return $query->cursorPaginate(20);
    }

    public function render()
    {
        return view('livewire.admin.finance.financial-ledger');
    }
}
