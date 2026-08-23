<?php

declare(strict_types=1);

namespace App\Livewire\Account\Orders;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('My Orders')]
final class OrderList extends Component
{
    use WithPagination;

    public string $tab = 'to_pay';

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $buyerId = Auth::id();

        $query = Order::where('buyer_user_id', $buyerId)
            ->with('shop')
            ->orderByDesc('created_at');

        match ($this->tab) {
            'to_pay' => $query->where('status', 'pending_payment'),
            'to_ship' => $query->where('status', 'paid')
                ->where(fn ($q) => $q->whereDoesntHave('fulfilments')->orWhereHas('fulfilments', fn ($f) => $f->where('status', 'pending'))),
            'to_receive' => $query->whereHas('fulfilments', fn ($f) => $f->whereIn('status', ['shipped', 'delivered'])),
            'completed' => $query->where('status', 'completed'),
            'disputed' => $query->whereHas('disputes', fn ($d) => $d->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])),
            default => null,
        };

        return $query->paginate(15);
    }

    public function render()
    {
        return view('livewire.account.orders.order-list');
    }
}
