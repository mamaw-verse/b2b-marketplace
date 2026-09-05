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

    public string $statusFilter = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function rfqs(): LengthAwarePaginator
    {
        $query = RfqRecipient::with(['rfq.buyer', 'shop', 'managedBy']);

        if ($this->search) {
            $query->whereHas('rfq', function ($q) {
                $q->where('title', 'ilike', '%'.$this->search.'%')
                    ->orWhereHas('buyer', fn ($bq) => $bq->where('email', 'ilike', '%'.$this->search.'%'));
            })->orWhereHas('shop', function ($q) {
                $q->where('storefront_name', 'ilike', '%'.$this->search.'%');
            });
        }

        if ($this->statusFilter) {
            $query->where('admin_status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.rfq.rfq-list');
    }
}
