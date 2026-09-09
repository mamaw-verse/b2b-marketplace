<?php

declare(strict_types=1);

namespace App\Livewire\Admin\SponsoredContent;

use App\Livewire\Forms\SponsoredContentForm;
use App\Models\SponsoredContent;
use App\Models\SponsoredContentPlacement;
use App\Models\SponsoredContentTier;
use App\Services\MediaUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Sponsored Content Composer')]
final class SponsoredContentComposer extends Component
{
    use WithFileUploads;

    /** @var list<string> */
    public const array PLACEMENT_KEYS = ['homepage', 'category_page', 'search_results'];

    /**
     * Recommended pixel dimensions for each creative orientation.
     * Shown as a hint next to the dropzone labels.
     */
    public const array CREATIVE_DIMENSIONS = [
        'wide' => '1200 × 300 px',
        'tall' => '400 × 600 px',
    ];

    public SponsoredContentForm $form;

    #[Locked]
    public ?int $contentId = null;

    /** @var TemporaryUploadedFile|null */
    public mixed $wideCreative = null;

    /** @var TemporaryUploadedFile|null */
    public mixed $tallCreative = null;

    public function mount(?SponsoredContent $sponsoredContent = null): void
    {
        Gate::authorize('sponsored_content.manage');

        if ($sponsoredContent && $sponsoredContent->exists) {
            $this->contentId = $sponsoredContent->id;
            $this->form->setSponsoredContent($sponsoredContent->load('placements'));
        } else {
            $defaultTier = SponsoredContentTier::where('is_active', true)
                ->orderBy('priority_weight', 'desc')
                ->first();

            if ($defaultTier) {
                $this->form->tierId = (string) $defaultTier->id;
            }
        }
    }

    /** @return Collection<int, SponsoredContentTier> */
    #[Computed]
    public function tiers(): Collection
    {
        return SponsoredContentTier::where('is_active', true)
            ->orderBy('priority_weight', 'desc')
            ->get();
    }

    public function save(MediaUploadService $uploader): void
    {
        Gate::authorize('sponsored_content.manage');

        $hasExistingWide = (bool) $this->form->wideMediaId;
        $hasExistingTall = (bool) $this->form->tallMediaId;

        $this->validate([
            'wideCreative' => $hasExistingWide
                ? ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp']
                : ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp'],
            'tallCreative' => $hasExistingTall
                ? ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp']
                : ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp'],
        ]);

        // At least one creative must be present (either existing or newly uploaded).
        if (! $hasExistingWide && ! $this->wideCreative && ! $hasExistingTall && ! $this->tallCreative) {
            $this->addError('wideCreative', __('Please upload at least one creative image (wide or tall).'));

            return;
        }

        $this->form->validate();

        if ($this->wideCreative) {
            $media = $uploader->store($this->wideCreative, Auth::id(), 'Sponsored content – wide creative');
            $this->form->wideMediaId = $media->id;
        }

        if ($this->tallCreative) {
            $media = $uploader->store($this->tallCreative, Auth::id(), 'Sponsored content – tall creative');
            $this->form->tallMediaId = $media->id;
        }

        $data = [
            'sponsored_content_tier_id' => (int) $this->form->tierId,
            'title' => $this->form->title,
            'wide_media_id' => $this->form->wideMediaId,
            'tall_media_id' => $this->form->tallMediaId,
            'destination_url' => $this->form->destinationUrl,
            'starts_at' => $this->form->startsAt,
            'ends_at' => $this->form->endsAt,
            'is_active' => $this->form->isActive,
            'created_by' => Auth::id(),
        ];

        if ($this->contentId) {
            $content = SponsoredContent::findOrFail($this->contentId);
            $content->update($data);
            $content->placements()->delete();
        } else {
            $content = SponsoredContent::create($data);
        }

        foreach ($this->form->placements as $key) {
            SponsoredContentPlacement::create([
                'sponsored_content_id' => $content->id,
                'placement_key' => $key,
            ]);
        }

        $this->redirect(route('admin.sponsored-content.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.sponsored-content.sponsored-content-composer');
    }
}
