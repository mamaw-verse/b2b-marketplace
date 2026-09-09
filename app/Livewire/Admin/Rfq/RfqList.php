<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Rfq;

use App\Models\RfqRecipient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('RFQs')]
final class RfqList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    /**
     * Per-tab counts for the RFQ admin status badges.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        $counts = RfqRecipient::query()
            ->selectRaw('admin_status, count(*) as aggregate')
            ->groupBy('admin_status')
            ->pluck('aggregate', 'admin_status');

        $pending = (int) $counts->get('pending', 0);
        $inProgress = (int) $counts->get('in_progress', 0);
        $resolved = (int) $counts->get('resolved', 0);
        $closed = (int) $counts->get('closed', 0);

        return [
            'all' => $pending + $inProgress + $resolved + $closed,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'closed' => $closed,
        ];
    }

    #[Computed]
    public function rfqs(): LengthAwarePaginator
    {
        $query = RfqRecipient::with(['rfq.buyer', 'shop', 'managedBy']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('rfq', function ($rq) {
                    $rq->where('title', 'ilike', '%'.$this->search.'%')
                        ->orWhereHas('buyer', fn ($bq) => $bq->where('email', 'ilike', '%'.$this->search.'%'));
                })->orWhereHas('shop', fn ($sq) => $sq->where('storefront_name', 'ilike', '%'.$this->search.'%'));
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('admin_status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.rfq.rfq-list');
    }
}
