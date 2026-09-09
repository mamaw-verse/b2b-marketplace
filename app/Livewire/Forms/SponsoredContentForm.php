<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\SponsoredContent;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class SponsoredContentForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|url|max:500')]
    public string $destinationUrl = '';

    #[Validate('required|exists:sponsored_content_tiers,id')]
    public ?string $tierId = null;

    #[Validate('required|date|before:endsAt')]
    public string $startsAt = '';

    #[Validate('required|date|after:startsAt')]
    public string $endsAt = '';

    #[Validate('boolean')]
    public bool $isActive = true;

    /**
     * At least one placement must be selected.
     *
     * @var list<string>
     */
    #[Validate('required|array|min:1')]
    public array $placements = [];

    /** Saved media ID for the wide (landscape) creative. */
    public ?int $wideMediaId = null;

    /** Saved media ID for the tall (portrait) creative. */
    public ?int $tallMediaId = null;

    public function setSponsoredContent(SponsoredContent $content): void
    {
        $this->title = $content->title;
        $this->destinationUrl = $content->destination_url;
        $this->tierId = (string) $content->sponsored_content_tier_id;
        $this->startsAt = $content->starts_at->format('Y-m-d\TH:i');
        $this->endsAt = $content->ends_at->format('Y-m-d\TH:i');
        $this->isActive = $content->is_active;
        $this->placements = $content->placements->pluck('placement_key')->toArray();
        $this->wideMediaId = $content->wide_media_id;
        $this->tallMediaId = $content->tall_media_id;
    }
}
