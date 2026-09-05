<?php

declare(strict_types=1);

namespace App\Livewire\Admin\SponsoredContent;

use App\Livewire\Forms\SponsoredContentForm;
use App\Models\SponsoredContent;
use App\Models\SponsoredContentPlacement;
use App\Models\SponsoredContentTier;
use App\Services\MediaUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Sponsored Content Composer')]
final class SponsoredContentComposer extends Component
{
    use WithFileUploads;

    /** @var list<string> */
    public const array PLACEMENT_KEYS = ['homepage', 'category_page', 'search_results'];

    public SponsoredContentForm $form;

    #[Locked]
    public ?int $contentId = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public mixed $creative = null;

    public function mount(?SponsoredContent $sponsoredContent = null): void
    {
        Gate::authorize('sponsored_content.manage');

        if ($sponsoredContent && $sponsoredContent->exists) {
            $this->contentId = $sponsoredContent->id;
            $this->form->setSponsoredContent($sponsoredContent->load('placements'));
        }
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, SponsoredContentTier> */
    #[Computed]
    public function tiers(): \Illuminate\Database\Eloquent\Collection
    {
        return SponsoredContentTier::where('is_active', true)
            ->orderBy('priority_weight', 'desc')
            ->get();
    }

    public function save(MediaUploadService $uploader): void
    {
        Gate::authorize('sponsored_content.manage');

        $this->validate([
            'creative' => $this->form->mediaId
                ? ['nullable', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp']
                : ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $this->form->validate();

        // Upload new creative if provided
        if ($this->creative) {
            $media = $uploader->store($this->creative, Auth::id(), 'Sponsored content creative');
            $this->form->mediaId = $media->id;
        }

        $data = [
            'sponsored_content_tier_id' => $this->form->tierId,
            'title' => $this->form->title,
            'media_id' => $this->form->mediaId,
            'destination_url' => $this->form->destinationUrl,
            'starts_at' => $this->form->startsAt,
            'ends_at' => $this->form->endsAt,
            'is_active' => $this->form->isActive,
            'created_by' => Auth::id(),
        ];

        if ($this->contentId) {
            $content = SponsoredContent::findOrFail($this->contentId);
            $content->update($data);
            // Sync placements
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.sponsored-content.sponsored-content-composer');
    }
}
