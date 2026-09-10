<?php

declare(strict_types=1);

namespace App\Livewire\Admin\SponsoredContent;

use App\Models\SponsoredContent;
use App\Models\SponsoredContentTier;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Sponsored Content')]
final class SponsoredContentList extends Component
{
    use WithPagination;

    // ── List filters ────────────────────────────────────────────────

    public string $tierFilter = '';

    public string $statusFilter = '';

    public string $placementFilter = '';

    public string $search = '';

    #[Locked]
    public ?int $confirmingDeleteId = null;

    #[Locked]
    public ?int $confirmingToggleId = null;

    // ── Tier modal state ────────────────────────────────────────────

    #[Locked]
    public ?int $tierEditingId = null;

    #[Validate('required|string|max:80')]
    public string $tierEditName = '';

    #[Validate('required|integer|min:1|max:10000')]
    public int $tierEditWeight = 50;

    #[Validate('boolean')]
    public bool $tierEditIsActive = true;

    #[Validate('required|string|max:80')]
    public string $newTierName = '';

    #[Validate('required|integer|min:1|max:10000')]
    public int $newTierWeight = 50;

    #[Validate('boolean')]
    public bool $newTierIsActive = true;

    #[Locked]
    public ?int $confirmingTierDeleteId = null;

    // ── Lifecycle ───────────────────────────────────────────────────

    public function mount(): void
    {
        Gate::authorize('sponsored_content.manage');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['tierFilter', 'statusFilter', 'placementFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    // ── Computed ────────────────────────────────────────────────────

    /** @return LengthAwarePaginator<SponsoredContent> */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $query = SponsoredContent::with(['tier', 'placements'])
            ->latest('created_at');

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($this->search).'%'])
                    ->orWhereRaw('LOWER(destination_url) LIKE ?', ['%'.mb_strtolower($this->search).'%']);
            });
        }

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

    /** Active tiers for the filter dropdown. */
    /** @return Collection<int, SponsoredContentTier> */
    #[Computed]
    public function tiers(): Collection
    {
        return SponsoredContentTier::orderBy('priority_weight', 'desc')->get();
    }

    /** All tiers for the tier-settings modal (includes inactive). */
    /** @return Collection<int, SponsoredContentTier> */
    #[Computed]
    public function allTiers(): Collection
    {
        return SponsoredContentTier::orderByDesc('priority_weight')->get();
    }

    // ── Content actions ─────────────────────────────────────────────

    public function confirmToggleActive(int $id): void
    {
        $this->confirmingToggleId = $id;
        Flux::modal('toggle-confirm')->show();
    }

    public function toggleActive(): void
    {
        Gate::authorize('sponsored_content.manage');

        if (! $this->confirmingToggleId) {
            return;
        }

        $item = SponsoredContent::findOrFail($this->confirmingToggleId);
        $item->update(['is_active' => ! $item->is_active]);
        $this->confirmingToggleId = null;
        unset($this->items);

        Flux::toast($item->is_active ? __('Content activated.') : __('Content deactivated.'), variant: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
        Flux::modal('delete-confirm')->show();
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

        Flux::toast(__('Sponsored content deleted.'), variant: 'success');
    }

    // ── Tier actions (modal) ────────────────────────────────────────

    public function startTierEdit(int $id): void
    {
        Gate::authorize('sponsored_content.manage');

        $tier = SponsoredContentTier::findOrFail($id);
        $this->tierEditingId = $tier->id;
        $this->tierEditName = $tier->name;
        $this->tierEditWeight = $tier->priority_weight;
        $this->tierEditIsActive = $tier->is_active;
    }

    public function updateTier(): void
    {
        Gate::authorize('sponsored_content.manage');

        $this->validateOnly('tierEditName');
        $this->validateOnly('tierEditWeight');

        SponsoredContentTier::findOrFail($this->tierEditingId)->update([
            'name' => trim($this->tierEditName),
            'priority_weight' => $this->tierEditWeight,
            'is_active' => $this->tierEditIsActive,
        ]);

        $this->reset('tierEditingId', 'tierEditName', 'tierEditWeight', 'tierEditIsActive');
        $this->tierEditIsActive = true;
        $this->tierEditWeight = 50;
        unset($this->allTiers, $this->tiers);

        Flux::toast(__('Tier updated.'), variant: 'success');
    }

    public function cancelTierEdit(): void
    {
        $this->reset('tierEditingId', 'tierEditName', 'tierEditWeight', 'tierEditIsActive');
        $this->tierEditIsActive = true;
        $this->tierEditWeight = 50;
    }

    public function toggleTierActive(int $id): void
    {
        Gate::authorize('sponsored_content.manage');

        $tier = SponsoredContentTier::findOrFail($id);
        $tier->update(['is_active' => ! $tier->is_active]);
        unset($this->allTiers, $this->tiers);

        Flux::toast($tier->is_active ? __('Tier activated.') : __('Tier deactivated.'), variant: 'success');
    }

    public function createTier(): void
    {
        Gate::authorize('sponsored_content.manage');

        $this->validateOnly('newTierName');
        $this->validateOnly('newTierWeight');

        SponsoredContentTier::create([
            'name' => trim($this->newTierName),
            'priority_weight' => $this->newTierWeight,
            'is_active' => $this->newTierIsActive,
        ]);

        $this->reset('newTierName', 'newTierWeight', 'newTierIsActive');
        $this->newTierIsActive = true;
        $this->newTierWeight = 50;
        unset($this->allTiers, $this->tiers);

        Flux::toast(__('Tier created.'), variant: 'success');
    }

    public function confirmTierDelete(int $id): void
    {
        $this->confirmingTierDeleteId = $id;
        Flux::modal('delete-tier-confirm')->show();
    }

    public function deleteTier(): void
    {
        Gate::authorize('sponsored_content.manage');

        if (! $this->confirmingTierDeleteId) {
            return;
        }

        $tier = SponsoredContentTier::findOrFail($this->confirmingTierDeleteId);

        if ($tier->sponsoredContent()->exists()) {
            Flux::toast(__('Cannot delete — tier is in use by sponsored content entries.'), variant: 'danger');
            $this->confirmingTierDeleteId = null;

            return;
        }

        $tier->delete();
        $this->confirmingTierDeleteId = null;
        unset($this->allTiers, $this->tiers);

        Flux::toast(__('Tier deleted.'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.sponsored-content.sponsored-content-list');
    }
}
