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
        return ShopOnboarding::with(['user'])
            ->where('status', $this->statusFilter)
            ->orderBy('submitted_at', 'desc')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.onboarding-queue');
    }
}
