<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub\Payouts;

use App\Models\Payout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Payout Detail')]
final class PayoutDetail extends Component
{
    public Payout $payout;

    public function mount(Payout $payout): void
    {
        // Ensure seller can only view their own
        $shop = Auth::user()->shop;
        abort_unless($shop && $payout->shop_id === $shop->id, 404);

        $this->payout = $payout->load(['items.order', 'disbursements']);
    }

    public function render()
    {
        return view('livewire.company-hub.payouts.payout-detail');
    }
}
