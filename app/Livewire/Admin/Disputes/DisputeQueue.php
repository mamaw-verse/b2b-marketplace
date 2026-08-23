<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Disputes;

use App\Models\Dispute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Dispute Queue')]
final class DisputeQueue extends Component
{
    use WithPagination;

    #[Computed]
    public function disputes(): LengthAwarePaginator
    {
        return Dispute::where('status', 'escalated')
            ->with(['order.shop', 'openedBy'])
            ->latest('escalated_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.disputes.dispute-queue');
    }
}
