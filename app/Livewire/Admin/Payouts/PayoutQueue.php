<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Payouts;

use App\Models\Payout;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Payout Queue')]
final class PayoutQueue extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function payouts(): LengthAwarePaginator
    {
        $query = Payout::with(['shop']);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->latest()->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.payouts.payout-queue');
    }
}
