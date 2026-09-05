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

    #[Validate('required|integer|exists:sponsored_content_tiers,id')]
    public ?int $tierId = null;

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

    /** Validated separately via request-layer mimes rule in the component. */
    public ?int $mediaId = null;

    public function setSponsoredContent(SponsoredContent $content): void
    {
        $this->title = $content->title;
        $this->destinationUrl = $content->destination_url;
        $this->tierId = $content->sponsored_content_tier_id;
        $this->startsAt = $content->starts_at->format('Y-m-d\TH:i');
        $this->endsAt = $content->ends_at->format('Y-m-d\TH:i');
        $this->isActive = $content->is_active;
        $this->placements = $content->placements->pluck('placement_key')->toArray();
        $this->mediaId = $content->media_id;
    }
}
