<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Jobs\RecordSponsoredContentEventJob;
use App\Models\SponsoredContent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class SponsoredContentBanner extends Component
{
    #[Locked]
    public string $placement;

    /**
     * Rendering orientation: "wide" renders a single weighted banner,
     * "tall" renders a rotating carousel of all tall-creative items.
     */
    #[Locked]
    public string $orientation = 'wide';

    /** Selected content ID for the wide single-pick render. */
    #[Locked]
    public ?int $selectedContentId = null;

    /** Ordered IDs for the tall carousel render (used server-side for impression tracking). */
    #[Locked]
    public array $carouselIds = [];

    public function mount(string $placement, string $orientation = 'wide'): void
    {
        $this->placement = $placement;
        $this->orientation = $orientation;

        if (! config('marketplace.features.sponsored_content_enabled')) {
            return;
        }

        if ($this->orientation === 'tall' || $this->orientation === 'row') {
            $this->initCarousel();
        } else {
            $this->initSinglePick();
        }
    }

    public function render(): View
    {
        $content = null;
        $carouselItems = collect();

        if ($this->orientation === 'tall' || $this->orientation === 'row') {
            /** @var Collection<int, SponsoredContent> $carouselItems */
            $carouselItems = SponsoredContent::with(['tier', 'tallMedia'])
                ->whereIn('id', $this->carouselIds)
                ->get()
                ->sortBy(fn ($c) => array_search($c->id, $this->carouselIds, true));
        } else {
            $content = $this->selectedContentId
                ? SponsoredContent::with('wideMedia')->find($this->selectedContentId)
                : null;
        }

        return view('livewire.shared.sponsored-content-banner', [
            'content' => $content,
            'carouselItems' => $carouselItems,
        ]);
    }

    // ── Private helpers ─────────────────────────────────────────────

    private function initSinglePick(): void
    {
        $selected = $this->selectWeighted();

        if ($selected) {
            $this->selectedContentId = $selected->id;
            RecordSponsoredContentEventJob::dispatch($selected->id, 'impression');
        }
    }

    private function initCarousel(): void
    {
        /** @var Collection<int, SponsoredContent> $items */
        $items = SponsoredContent::with('tier')
            ->active()
            ->forPlacement($this->placement)
            ->whereNotNull('tall_media_id')
            ->orderByDesc('id')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        // Store IDs; dispatch impressions for all items shown in the carousel.
        $this->carouselIds = $items->pluck('id')->toArray();

        foreach ($this->carouselIds as $id) {
            RecordSponsoredContentEventJob::dispatch($id, 'impression');
        }
    }

    /**
     * Tier-weighted random selection for the wide single-pick variant.
     */
    private function selectWeighted(): ?SponsoredContent
    {
        $candidates = SponsoredContent::with(['tier', 'wideMedia'])
            ->active()
            ->forPlacement($this->placement)
            ->whereNotNull('wide_media_id')
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
