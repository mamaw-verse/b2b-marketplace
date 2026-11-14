<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Orders;

use App\Models\Order;
use App\Models\Quotation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Orders')]
final class OrderQueue extends Component
{
    use WithPagination;

    public string $tab = 'all';

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    /**
     * Awarded quotations belonging to this shop that don't yet have an order.
     *
     * @return Collection<int, Quotation>
     */
    #[Computed]
    public function awardedQuotations(): Collection
    {
        $shop = Auth::user()->activeShop;

        if (! $shop) {
            return new Collection;
        }

        return Quotation::where('status', 'awarded')
            ->whereDoesntHave('order')
            ->whereHas('rfqRecipient', fn ($q) => $q->where('shop_id', $shop->id))
            ->with(['rfq:id,title', 'rfqRecipient:id,rfq_id,shop_id'])
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Per-tab order counts for the tab badges. Keys match the tab keys used in the view.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        $shop = Auth::user()->activeShop;

        if (! $shop) {
            return ['all' => 0, 'draft' => 0, 'pending_payment' => 0, 'to_fulfil' => 0, 'disputed' => 0];
        }

        $baseQuery = Order::where('shop_id', $shop->id);

        return [
            'all' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'pending_payment' => (clone $baseQuery)->where('status', 'pending_payment')->count(),
            'to_fulfil' => (clone $baseQuery)->where('status', 'paid')->count(),
            'disputed' => (clone $baseQuery)->whereHas('disputes', fn ($q) => $q->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn']))->count(),
        ];
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $shop = Auth::user()->activeShop;

        if (! $shop) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        $query = Order::where('shop_id', $shop->id)
            ->with('buyer')
            ->orderByDesc('created_at');

        match ($this->tab) {
            'draft' => $query->where('status', 'draft'),
            'pending_payment' => $query->where('status', 'pending_payment'),
            'to_fulfil' => $query->where('status', 'paid'),
            'disputed' => $query->whereHas('disputes', fn ($q) => $q->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])),
            default => null,
        };

        return $query->paginate(15);
    }

    public function render()
    {
        return view('livewire.company-hub.orders.order-queue');
    }
}
