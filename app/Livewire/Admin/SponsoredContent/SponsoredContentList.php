<?php

declare(strict_types=1);

namespace App\Livewire\Admin\SponsoredContent;

use App\Models\SponsoredContent;
use App\Models\SponsoredContentTier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Sponsored Content')]
final class SponsoredContentList extends Component
{
    use WithPagination;

    public string $tierFilter = '';

    public string $statusFilter = '';

    public string $placementFilter = '';

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        Gate::authorize('sponsored_content.manage');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['tierFilter', 'statusFilter', 'placementFilter'], true)) {
            $this->resetPage();
        }
    }

    /** @return LengthAwarePaginator<SponsoredContent> */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $query = SponsoredContent::with(['tier', 'placements'])
            ->latest('created_at');

        if ($this->tierFilter) {
            $query->where('sponsored_content_tier_id', $this->tierFilter);
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now());
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        } elseif ($this->statusFilter === 'scheduled') {
            $query->where('is_active', true)->where('starts_at', '>', now());
        } elseif ($this->statusFilter === 'expired') {
            $query->where('ends_at', '<', now());
        }

        if ($this->placementFilter) {
            $query->whereHas('placements', fn ($q) => $q->where('placement_key', $this->placementFilter));
        }

        return $query->paginate(15);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, SponsoredContentTier> */
    #[Computed]
    public function tiers(): \Illuminate\Database\Eloquent\Collection
    {
        return SponsoredContentTier::orderBy('priority_weight', 'desc')->get();
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('sponsored_content.manage');

        $item = SponsoredContent::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);
        unset($this->items);

        flux()->toast($item->is_active ? 'Content activated.' : 'Content deactivated.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
        \Flux\Flux::modal('delete-confirm')->show();
    }

    public function delete(): void
    {
        Gate::authorize('sponsored_content.manage');

        if (! $this->confirmingDeleteId) {
            return;
        }

        SponsoredContent::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        unset($this->items);

        flux()->toast('Sponsored content deleted.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.sponsored-content.sponsored-content-list');
    }
}
