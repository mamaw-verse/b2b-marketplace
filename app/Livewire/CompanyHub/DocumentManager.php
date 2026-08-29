<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\Media;
use App\Models\Product;
use App\Services\MediaUploadService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Document Manager')]
final class DocumentManager extends Component
{
    use WithFileUploads;

    public Product $product;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newDocuments = [];

    public ?int $confirmingDocumentId = null;

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);
        $this->product = $product;
    }

    public function uploadDocuments(MediaUploadService $uploader): void
    {
        $this->validate([
            'newDocuments' => ['required', 'array', 'min:1'],
            'newDocuments.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp'],
        ]);

        foreach ($this->newDocuments as $doc) {
            $media = $uploader->store($doc, Auth::id(), 'Product document');
            $this->product->documents()->attach($media->id);
        }

        $this->reset('newDocuments');
        unset($this->documents, $this->hasPendingDocuments);

        \Flux\Flux::toast('Documents uploaded successfully.', variant: 'success');
    }

    public function confirmRemoveDocument(int $mediaId): void
    {
        $this->confirmingDocumentId = $mediaId;
        \Flux\Flux::modal('remove-document-confirm')->show();
    }

    public function removeDocument(): void
    {
        if (! $this->confirmingDocumentId) {
            return;
        }

        $this->product->documents()->detach($this->confirmingDocumentId);

        $this->confirmingDocumentId = null;
        unset($this->documents, $this->hasPendingDocuments);

        \Flux\Flux::toast('Document removed.', variant: 'success');
    }

    /**
     * @return Collection<int, Media>
     */
    #[Computed]
    public function documents(): Collection
    {
        return $this->product->documents()->orderByDesc('created_at')->get();
    }

    /**
     * Whether any attached document is still awaiting its antivirus scan.
     * Drives the table's auto-refresh: the view polls only while this is true
     * and goes idle once every document has settled (clean/infected).
     */
    #[Computed]
    public function hasPendingDocuments(): bool
    {
        return $this->documents->contains(
            fn (Media $document): bool => $document->scan_status === 'pending'
        );
    }

    public function render()
    {
        return view('livewire.company-hub.document-manager');
    }
}
