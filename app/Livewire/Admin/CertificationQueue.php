<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\CertificationApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Certification Queue')]
final class CertificationQueue extends Component
{
    use WithPagination;

    public string $statusFilter = 'interested';

    #[Computed]
    public function applications(): LengthAwarePaginator
    {
        return CertificationApplication::query()
            ->with('shop.user')
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->paginate(15);
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        $counts = CertificationApplication::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'all' => (int) $counts->sum(),
            'interested' => (int) $counts->get('interested', 0),
            'contacted' => (int) $counts->get('contacted', 0),
        ];
    }

    public function markContacted(int $applicationId): void
    {
        $application = CertificationApplication::query()->with('shop')->findOrFail($applicationId);
        $application->update([
            'status' => 'contacted',
            'addressed_by' => Auth::id(),
            'addressed_at' => now(),
        ]);

        unset($this->applications);
        session()->flash('message', 'Seller marked as contacted.');
    }

    public function render()
    {
        return view('livewire.admin.certification-queue');
    }
}
