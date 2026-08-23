<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Hub;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Customers')]
final class CustomersList extends Component
{
    use WithPagination;

    #[Computed]
    public function customers(): LengthAwarePaginator
    {
        $shop = Auth::user()->shop;
        if (! $shop) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        return User::whereHas('orders', fn ($q) => $q->where('shop_id', $shop->id))
            ->withCount(['orders' => fn ($q) => $q->where('shop_id', $shop->id)])
            ->orderByDesc('orders_count')
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.company-hub.hub.customers-list');
    }
}
