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

    public function confirmReceived(int $orderId): void
    {
        $order = Order::where('buyer_user_id', Auth::id())->findOrFail($orderId);

        $order->update(['status' => 'completed']);
        $order->fulfilments()->update(['status' => 'delivered']);

        if ($order->shop->user) {
            $order->shop->user->notify(new \App\Notifications\OrderCompletedNotification($order));
        }

        \Flux\Flux::toast('Order confirmed as received.', variant: 'success');
    }

    #[Computed]
    public function tabCounts(): array
    {
        $buyerId = Auth::id();

        $baseQuery = Order::where('buyer_user_id', $buyerId);

        return [
            'to_pay' => (clone $baseQuery)->where('status', 'pending_payment')->count(),
            'to_ship' => (clone $baseQuery)->where('status', 'paid')
                ->where(fn ($q) => $q->whereDoesntHave('fulfilments')->orWhereHas('fulfilments', fn ($f) => $f->where('status', 'pending')))->count(),
            'to_receive' => (clone $baseQuery)->whereNotIn('status', ['completed', 'cancelled', 'refunded'])
                ->whereHas('fulfilments', fn ($f) => $f->whereIn('status', ['shipped', 'delivered']))->count(),
            'completed' => (clone $baseQuery)->whereIn('status', ['completed', 'refunded'])->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'disputed' => (clone $baseQuery)->whereHas('disputes', fn ($d) => $d->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn']))->count(),
        ];
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $buyerId = Auth::id();

        $query = Order::where('buyer_user_id', $buyerId)
            ->with(['shop', 'items', 'fulfilments.fulfilmentItems'])
            ->orderByDesc('created_at');

        match ($this->tab) {
            'to_pay' => $query->where('status', 'pending_payment'),
            'to_ship' => $query->where('status', 'paid')
                ->where(fn ($q) => $q->whereDoesntHave('fulfilments')->orWhereHas('fulfilments', fn ($f) => $f->where('status', 'pending'))),
            'to_receive' => $query->whereNotIn('status', ['completed', 'cancelled', 'refunded'])
                ->whereHas('fulfilments', fn ($f) => $f->whereIn('status', ['shipped', 'delivered'])),
            'completed' => $query->whereIn('status', ['completed', 'refunded']),
            'cancelled' => $query->where('status', 'cancelled'),
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
