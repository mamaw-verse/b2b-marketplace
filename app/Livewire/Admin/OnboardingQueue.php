<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\ShopOnboarding;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Onboarding Queue')]
final class OnboardingQueue extends Component
{
    use WithPagination;

    public string $statusFilter = 'submitted';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<ShopOnboarding>
     */
    #[Computed]
    public function onboardings(): LengthAwarePaginator
    {
        $query = ShopOnboarding::with(['user']);

        if ($this->statusFilter === 'all') {
            $query->whereIn('status', ['submitted', 'pending', 'requires_revision']);
        } else {
            $query->where('status', $this->statusFilter);
        }

        return $query
            ->orderBy('submitted_at', 'desc')
            ->paginate(15);
    }

    /**
     * Per-tab submission counts for the queue badges.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        $counts = ShopOnboarding::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $submitted = (int) $counts->get('submitted', 0);
        $pending = (int) $counts->get('pending', 0);
        $requiresRevision = (int) $counts->get('requires_revision', 0);

        return [
            'all' => $submitted + $pending + $requiresRevision,
            'submitted' => $submitted,
            'pending' => $pending,
            'requires_revision' => $requiresRevision,
            'approved' => (int) $counts->get('approved', 0),
            'rejected' => (int) $counts->get('rejected', 0),
        ];
    }

    public function render()
    {
        return view('livewire.admin.onboarding-queue');
    }
}
