<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Payouts;

use App\Models\Payout;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Payout History')]
final class PayoutHistory extends Component
{
    use WithPagination;

    #[Computed]
    public function payouts(): LengthAwarePaginator
    {
        $shop = Auth::user()->shop;

        if (! $shop) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        return Payout::where('shop_id', $shop->id)
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.company-hub.payouts.payout-history');
    }
}
