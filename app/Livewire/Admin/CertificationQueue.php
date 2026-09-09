<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasDocumentViewer;
use App\Models\CertificationApplication;
use App\Models\ShopCertificate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Certification Queue')]
final class CertificationQueue extends Component
{
    use HasDocumentViewer;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    /**
     * Halal certifications across all shops, newest first.
     */
    #[Computed]
    public function certificates(): LengthAwarePaginator
    {
        return ShopCertificate::query()
            ->with('shop')
            ->when($this->search !== '', fn ($query) => $query->whereHas(
                'shop',
                fn ($shopQuery) => $shopQuery->where('registered_business_name', 'ilike', '%'.$this->search.'%')
            ))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('expiry_date')
            ->paginate(15);
    }

    /**
     * Certification applications logged for reference, newest first.
     */
    #[Computed]
    public function applications(): LengthAwarePaginator
    {
        return CertificationApplication::query()
            ->with('shop.user')
            ->latest('created_at')
            ->paginate(15);
    }

    /**
     * Open an application's uploaded document in the preview modal.
     */
    public function viewApplicationDocument(int $applicationId): void
    {
        $application = CertificationApplication::query()->findOrFail($applicationId);

        if (! $this->canPreviewApplicationDocument($application->document_path)) {
            return;
        }

        $this->viewingMediaId = null;
        $this->documentPreviewUrl = $this->applicationDocumentUrl($application->id);
        $this->showDocumentViewer = true;
    }

    /**
     * Whether an application's uploaded document can be previewed inline
     * (PDFs and images) as opposed to only being downloadable (docx, doc...).
     */
    public function canPreviewApplicationDocument(?string $documentPath): bool
    {
        if ($documentPath === null) {
            return false;
        }

        return in_array(
            str(pathinfo($documentPath, PATHINFO_EXTENSION))->lower()->toString(),
            ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
            true
        );
    }

    /**
     * In-app streaming URL for an application's uploaded document. Files that
     * cannot be previewed inline stream as an attachment download. Returns null
     * when the application has no uploaded document.
     */
    public function applicationDocumentUrl(int $applicationId, bool $download = false): ?string
    {
        $application = CertificationApplication::query()->find($applicationId);

        if ($application === null || $application->document_path === null) {
            return null;
        }

        $parameters = ['application' => $application->id];

        if ($download) {
            $parameters['download'] = '1';
        }

        return route('admin.certifications.applications.document', $parameters);
    }

    public function render()
    {
        return view('livewire.admin.certification-queue');
    }
}
