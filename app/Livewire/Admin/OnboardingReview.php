<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\ApproveOnboardingAction;
use App\Events\OnboardingStatusChanged;
use App\Models\Category;
use App\Models\Media;
use App\Models\ShopOnboarding;
use App\Services\MediaUploadService;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Onboarding Review')]
final class OnboardingReview extends Component
{
    public ShopOnboarding $onboarding;

    public string $notes = '';

    public string $action = '';

    public ?int $viewingMediaId = null;

    public ?string $documentPreviewUrl = null;

    public bool $showDocumentViewer = false;

    public bool $showActionModal = false;

    #[Computed]
    public function productCategoryNames(): array
    {
        if (! $this->onboarding->product_categories) {
            return [];
        }

        $ids = collect(explode(',', $this->onboarding->product_categories))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Category::whereIn('id', $ids)
            ->get(['id', 'name'])
            ->pluck('name')
            ->toArray();
    }

    public function mount(ShopOnboarding $onboarding): void
    {
        $this->onboarding = $onboarding->load(['user', 'certificates.media', 'documents']);
    }

    public function approve(ApproveOnboardingAction $approveAction): void
    {
        if ($this->onboarding->status !== 'submitted') {
            return;
        }

        $approveAction->execute($this->onboarding, Auth::user());

        session()->flash('message', 'Onboarding approved successfully. Shop has been created.');

        $this->redirectRoute('admin.onboardings.index');
    }

    public function openActionModal(string $action): void
    {
        if ($this->onboarding->status !== 'submitted') {
            return;
        }

        $this->action = $action;
        $this->notes = '';
        $this->showActionModal = true;
    }

    public function submitAction(): void
    {
        if ($this->onboarding->status !== 'submitted') {
            return;
        }

        $this->validate([
            'notes' => 'required|string|min:1',
        ]);

        $status = match ($this->action) {
            'request_revision' => 'requires_revision',
            'reject' => 'rejected',
            default => throw new InvalidArgumentException("Invalid action: {$this->action}"),
        };

        $this->onboarding->update([
            'status' => $status,
            'admin_notes' => $this->notes,
            'reviewed_at' => now(),
            'reviewed_by_id' => Auth::id(),
        ]);

        OnboardingStatusChanged::dispatch($this->onboarding->fresh());

        $this->showActionModal = false;
        $this->notes = '';
        $this->action = '';

        session()->flash('message', "Onboarding status updated to {$status}.");

        $this->redirectRoute('admin.onboardings.index');
    }

    public function viewDocument(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);
        $this->viewingMediaId = $mediaId;
        $this->documentPreviewUrl = app(MediaUploadService::class)->signedUrl($media);
        $this->showDocumentViewer = true;
    }

    public function closeDocumentViewer(): void
    {
        $this->viewingMediaId = null;
        $this->documentPreviewUrl = null;
        $this->showDocumentViewer = false;
    }

    public function render()
    {
        return view('livewire.admin.onboarding-review');
    }
}
