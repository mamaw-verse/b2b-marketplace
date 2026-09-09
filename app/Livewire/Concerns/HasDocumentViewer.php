<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Media;
use App\Services\MediaUploadService;

/**
 * Adds a document/image preview modal to any Livewire component.
 *
 * Usage:
 *   1. Add `use HasDocumentViewer;` to the component class.
 *   2. Call `$this->viewDocument($mediaId)` from an action.
 *   3. Include `@include('livewire.admin.document-viewer')` at the bottom of the view.
 */
trait HasDocumentViewer
{
    public ?int $viewingMediaId = null;

    public ?string $documentPreviewUrl = null;

    public bool $showDocumentViewer = false;

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
}
