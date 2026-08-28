<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('Dashboard')]
final class Dashboard extends Component
{
    /**
     * Order status counts for the buyer's summary cards.
     *
     * @return array{to_pay: int, to_ship: int, to_receive: int, completed: int}
     */
    #[Computed]
    public function statusCounts(): array
    {
        $buyerId = Auth::id();

        return [
            'to_pay' => Order::where('buyer_user_id', $buyerId)
                ->where('status', 'pending_payment')->count(),
            'to_ship' => Order::where('buyer_user_id', $buyerId)
                ->where('status', 'paid')
                ->where(fn ($q) => $q->whereDoesntHave('fulfilments')
                    ->orWhereHas('fulfilments', fn ($f) => $f->where('status', 'pending')))
                ->count(),
            'to_receive' => Order::where('buyer_user_id', $buyerId)
                ->whereHas('fulfilments', fn ($f) => $f->whereIn('status', ['shipped', 'delivered']))
                ->count(),
            'completed' => Order::where('buyer_user_id', $buyerId)
                ->where('status', 'completed')->count(),
        ];
    }

    /**
     * Most recent orders for a quick glance.
     *
     * @return Collection<int, Order>
     */
    #[Computed]
    public function recentOrders(): Collection
    {
        return Order::where('buyer_user_id', Auth::id())
            ->with('shop')
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function hasShop(): bool
    {
        return Auth::user()->shop()->exists();
    }

    public function render()
    {
        return view('livewire.account.dashboard');
    }
}
