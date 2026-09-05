<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\RfqRecipient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('RFQs Received')]
final class RfqQueue extends Component
{
    public string $statusFilter = '';

    public function updatedStatusFilter(): void
    {
        // Reset pagination on filter change
    }

    #[Computed]
    public function rfqs()
    {
        $shop = Auth::user()->shop;

        if (! $shop) {
            return collect();
        }

        $query = RfqRecipient::where('shop_id', $shop->id)
            ->with(['rfq.user.buyerProfile'])
            ->orderByDesc('created_at');

        if ($this->statusFilter) {
            // The 'closed' tab includes both explicit closes and system timeout (no_bid).
            $statuses = $this->statusFilter === 'closed'
                ? ['closed', 'no_bid']
                : [$this->statusFilter];

            $query->whereIn('status', $statuses);
        }

        return $query->cursorPaginate(20);
    }

    #[Computed]
    public function statusCounts(): array
    {
        $shop = Auth::user()->shop;

        if (! $shop) {
            return ['received' => 0, 'reviewed' => 0, 'in_progress' => 0, 'closed' => 0];
        }

        $counts = RfqRecipient::where('shop_id', $shop->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'received' => $counts['received'] ?? 0,
            'reviewed' => $counts['reviewed'] ?? 0,
            'in_progress' => $counts['in_progress'] ?? 0,
            'closed' => ($counts['closed'] ?? 0) + ($counts['no_bid'] ?? 0),
        ];
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
    }

    public function render()
    {
        return view('livewire.company-hub.rfq-queue');
    }
}
