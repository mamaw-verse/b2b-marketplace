<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('Payment History')]
final class PaymentHistory extends Component
{
    use WithPagination;

    public string $status = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function updatedStatus(): void
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

    #[Computed]
    public function transactions(): CursorPaginator
    {
        $buyerId = Auth::id();

        // Succeeded payments
        $payments = DB::table('payment_attempts')
            ->join('order_payment_terms', 'order_payment_terms.id', '=', 'payment_attempts.order_payment_term_id')
            ->join('orders', 'orders.id', '=', 'order_payment_terms.order_id')
            ->where('orders.buyer_user_id', $buyerId)
            ->where('payment_attempts.status', 'succeeded')
            ->select([
                'payment_attempts.id',
                DB::raw("'payment' as resource_type"),
                DB::raw('CAST(payment_attempts.dlt_amount AS NUMERIC(12,2)) as amount'),
                'orders.id as order_id',
                DB::raw('CAST(NULL AS TEXT) as note'),
                'payment_attempts.succeeded_at as date',
            ]);

        // Refunds
        $refunds = DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.buyer_user_id', $buyerId)
            ->where('ledger_entries.type', 'refund')
            ->select([
                'ledger_entries.id',
                DB::raw("'refund' as resource_type"),
                'ledger_entries.amount',
                'orders.id as order_id',
                DB::raw('CAST(ledger_entries.note AS TEXT) as note'),
                'ledger_entries.created_at as date',
            ]);

        if ($this->status === 'paid') {
            $query = $payments;
        } elseif ($this->status === 'refunded') {
            $query = $refunds;
        } else {
            $query = $payments->unionAll($refunds);
        }

        $wrapped = DB::query()->fromSub($query, 'transactions');

        if ($this->dateFrom) {
            $wrapped->where('date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $wrapped->where('date', '<=', $this->dateTo.' 23:59:59');
        }

        $wrapped->orderByDesc('date');

        return $wrapped->cursorPaginate(15);
    }

    public function render()
    {
        return view('livewire.account.payment-history');
    }
}
