<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Jobs\RecordSponsoredContentEventJob;
use App\Models\SponsoredContent;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class SponsoredContentBanner extends Component
{
    #[Locked]
    public string $placement;

    /** @var int|null The ID of the selected content item for this render. */
    #[Locked]
    public ?int $selectedContentId = null;

    public function mount(string $placement): void
    {
        $this->placement = $placement;

        if (! config('marketplace.features.sponsored_content_enabled')) {
            return;
        }

        $selected = $this->selectContent();

        if ($selected) {
            $this->selectedContentId = $selected->id;
            // Fire impression event asynchronously — off the page-load critical path.
            RecordSponsoredContentEventJob::dispatch($selected->id, 'impression');
        }
    }

    public function render(): View
    {
        $content = $this->selectedContentId
            ? SponsoredContent::with('media')->find($this->selectedContentId)
            : null;

        return view('livewire.shared.sponsored-content-banner', [
            'content' => $content,
        ]);
    }

    /**
     * Perform a tier-weighted random selection of one sponsored content item
     * for the configured placement. Higher priority_weight = proportionally
     * higher chance of being selected.
     */
    private function selectContent(): ?SponsoredContent
    {
        $candidates = SponsoredContent::with(['tier', 'media'])
            ->active()
            ->forPlacement($this->placement)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $totalWeight = $candidates->sum(fn ($c) => $c->tier?->priority_weight ?? 1);

        if ($totalWeight <= 0) {
            return $candidates->random();
        }

        $random = random_int(1, $totalWeight);
        $cumulative = 0;

        foreach ($candidates as $candidate) {
            $cumulative += $candidate->tier?->priority_weight ?? 1;
            if ($random <= $cumulative) {
                return $candidate;
            }
        }

        return $candidates->first();
    }
}
