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
        $shop = Auth::user()->shop;

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

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        $shop = Auth::user()->shop;

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
