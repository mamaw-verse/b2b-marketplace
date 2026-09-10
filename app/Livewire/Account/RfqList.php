<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('My RFQs')]
final class RfqList extends Component
{
    public string $statusFilter = '';

    public function render()
    {
        $query = Auth::user()->rfqs()
            ->with('recipients')
            ->withCount('recipients');

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $rfqs = $query->orderByDesc('created_at')->cursorPaginate(20);

        return view('livewire.account.rfq-list', [
            'rfqs' => $rfqs,
        ]);
    }
}
