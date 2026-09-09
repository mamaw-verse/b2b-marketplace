<?php

declare(strict_types=1);

namespace App\Livewire\Admin\SponsoredContent;

use App\Models\SponsoredContentTier;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Tier Settings — Sponsored Content')]
final class SponsoredContentTierSettings extends Component
{
    // ── New-tier form ───────────────────────────────────────────────

    #[Validate('required|string|max:80')]
    public string $newName = '';

    #[Validate('required|integer|min:1|max:10000')]
    public int $newWeight = 50;

    #[Validate('boolean')]
    public bool $newIsActive = true;

    // ── Edit state ──────────────────────────────────────────────────

    #[Locked]
    public ?int $editingId = null;

    #[Validate('required|string|max:80')]
    public string $editName = '';

    #[Validate('required|integer|min:1|max:10000')]
    public int $editWeight = 50;

    #[Validate('boolean')]
    public bool $editIsActive = true;

    // ── Delete confirmation ─────────────────────────────────────────

    #[Locked]
    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        Gate::authorize('sponsored_content.manage');
    }

    /**
     * @return Collection<int, SponsoredContentTier>
     */
    public function getTiersProperty(): Collection
    {
        return SponsoredContentTier::orderByDesc('priority_weight')->get();
    }

    public function createTier(): void
    {
        Gate::authorize('sponsored_content.manage');

        $this->validateOnly('newName');
        $this->validateOnly('newWeight');

        SponsoredContentTier::create([
            'name' => trim($this->newName),
            'priority_weight' => $this->newWeight,
            'is_active' => $this->newIsActive,
        ]);

        $this->reset('newName', 'newWeight', 'newIsActive');
        $this->newIsActive = true;
        $this->newWeight = 50;

        flux()->toast(__('Tier created.'));
    }

    public function startEdit(int $id): void
    {
        Gate::authorize('sponsored_content.manage');

        $tier = SponsoredContentTier::findOrFail($id);
        $this->editingId = $tier->id;
        $this->editName = $tier->name;
        $this->editWeight = $tier->priority_weight;
        $this->editIsActive = $tier->is_active;
    }

    public function updateTier(): void
    {
        Gate::authorize('sponsored_content.manage');

        $this->validateOnly('editName');
        $this->validateOnly('editWeight');

        SponsoredContentTier::findOrFail($this->editingId)->update([
            'name' => trim($this->editName),
            'priority_weight' => $this->editWeight,
            'is_active' => $this->editIsActive,
        ]);

        $this->reset('editingId', 'editName', 'editWeight', 'editIsActive');
        $this->editIsActive = true;

        flux()->toast(__('Tier updated.'));
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editName', 'editWeight', 'editIsActive');
        $this->editIsActive = true;
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('sponsored_content.manage');

        $tier = SponsoredContentTier::findOrFail($id);
        $tier->update(['is_active' => ! $tier->is_active]);

        flux()->toast($tier->is_active ? __('Tier activated.') : __('Tier deactivated.'));
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
        Flux::modal('delete-tier-confirm')->show();
    }

    public function deleteTier(): void
    {
        Gate::authorize('sponsored_content.manage');

        if (! $this->confirmingDeleteId) {
            return;
        }

        $tier = SponsoredContentTier::findOrFail($this->confirmingDeleteId);

        if ($tier->sponsoredContent()->exists()) {
            flux()->toast(__('Cannot delete — tier is in use by sponsored content entries.'), variant: 'danger');
            $this->confirmingDeleteId = null;

            return;
        }

        $tier->delete();
        $this->confirmingDeleteId = null;

        flux()->toast(__('Tier deleted.'));
    }

    public function render(): View
    {
        return view('livewire.admin.sponsored-content.sponsored-content-tier-settings');
    }
}
