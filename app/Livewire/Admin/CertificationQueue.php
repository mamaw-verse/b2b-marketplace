<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HasDocumentViewer;
use App\Models\CertificationApplication;
use App\Models\ShopCertificate;
use App\Notifications\CertificateApprovedNotification;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'admin'])]
#[Title('Certification Queue')]
final class CertificationQueue extends Component
{
    use HasDocumentViewer;
    use WithPagination;

    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    /** The certificate currently loaded in the document viewer modal. */
    public ?int $viewingCertificateId = null;

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
     * Open a shop certificate's document in the viewer and track which
     * certificate it belongs to so the Approve button can reference it.
     */
    public function viewCertificate(int $certificateId): void
    {
        $certificate = ShopCertificate::query()->findOrFail($certificateId);

        $this->viewingCertificateId = $certificateId;
        $this->viewDocument($certificate->media_id);
    }

    /**
     * Approve a certificate: transition to 'active', mark verified,
     * and notify the shop owner.
     */
    public function approveCertificate(int $certificateId): void
    {
        $certificate = ShopCertificate::query()
            ->with('shop.user')
            ->findOrFail($certificateId);

        DB::transaction(function () use ($certificate): void {
            $certificate->update([
                'status' => 'active',
                'is_verified' => true,
            ]);
        });

        $shopOwner = $certificate->shop?->user;

        if ($shopOwner !== null) {
            $shopOwner->notify(new CertificateApprovedNotification($certificate));
        }

        unset($this->certificates);
        $this->viewingCertificateId = null;
        $this->showDocumentViewer = false;

        Flux::toast(__('Certificate approved successfully.'), variant: 'success');
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

        $this->viewingCertificateId = null;
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
