<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Payouts;

use App\Actions\ReleasePayoutAction;
use App\Models\Payout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Payout Detail')]
final class PayoutDetail extends Component
{
    public Payout $payout;

    public function mount(Payout $payout): void
    {
        $this->payout = $payout->load(['shop', 'releasedBy', 'items.order', 'disbursements']);
    }

    public function release(): void
    {
        try {
            $result = app(ReleasePayoutAction::class)->execute($this->payout, Auth::user());

            if ($result['success']) {
                session()->flash('success', 'Payout disbursement completed successfully.');
            } else {
                session()->flash('error', $result['error'] ?? 'Disbursement failed.');
            }

            $this->payout = $this->payout->fresh()->load(['shop', 'releasedBy', 'items.order', 'disbursements']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first());
        }
    }

    public function retry(): void
    {
        $this->release();
    }

    public function render()
    {
        return view('livewire.admin.payouts.payout-detail');
    }
}
