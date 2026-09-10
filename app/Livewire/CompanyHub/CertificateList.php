<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Livewire\Concerns\HasDocumentViewer;
use App\Models\Media;
use App\Models\ShopCertificate;
use App\Models\User;
use App\Notifications\CertificateSubmittedNotification;
use App\Services\MediaUploadService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Certificates')]
final class CertificateList extends Component
{
    use HasDocumentViewer;
    use WithFileUploads;

    public bool $showAddModal = false;

    public string $expiry_date = '';

    /** @var TemporaryUploadedFile|null */
    public $certificateFile = null;

    public ?int $uploadedMediaId = null;

    public ?string $uploadedFilename = null;

    public ?string $uploadedScanStatus = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->shop !== null, 404);
    }

    /**
     * Halal certificates for the current seller's shop, newest first.
     *
     * @return Collection<int, ShopCertificate>
     */
    #[Computed]
    public function certificates(): Collection
    {
        return Auth::user()->shop
            ->certificates()
            ->with('media')
            ->orderByDesc('expiry_date')
            ->get();
    }

    public function openAddModal(): void
    {
        $this->resetAddFields();
        $this->showAddModal = true;
    }

    /**
     * Lifecycle hook: auto-upload the certificate file as soon as it is
     * selected via the dropzone wire:model, mirroring the onboarding wizard.
     */
    public function updatedCertificateFile(mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $this->resetErrorBag('certificateFile');

        try {
            $media = app(MediaUploadService::class)->store($value, Auth::id(), 'halal_certificate');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            $this->addError('certificateFile', $message);

            return;
        } catch (Throwable $e) {
            $this->addError('certificateFile', $e->getMessage());

            return;
        }

        $this->uploadedMediaId = $media->id;
        $this->uploadedFilename = $media->original_filename;
        $this->uploadedScanStatus = $media->scan_status;
        unset($this->certificateFile);
    }

    public function replaceCertificateFile(): void
    {
        $this->reset(['certificateFile', 'uploadedMediaId', 'uploadedFilename', 'uploadedScanStatus']);
        $this->resetErrorBag('certificateFile');
    }

    /**
     * Polled every 5 s while the uploaded file is still scanning.
     * Re-reads scan_status from the DB and updates the local property.
     */
    public function refreshScanStatus(): void
    {
        if (! $this->uploadedMediaId) {
            return;
        }

        $media = Media::find($this->uploadedMediaId);

        if ($media === null) {
            return;
        }

        $this->uploadedScanStatus = $media->scan_status;
    }

    /**
     * True only when the form is fully ready to submit:
     * expiry date filled, a file uploaded, and that file passed its scan.
     * Not cached — evaluated fresh on every render so the button reacts
     * immediately as the user fills each field.
     */
    public function isReadyToSave(): bool
    {
        return $this->expiry_date !== ''
            && $this->uploadedMediaId !== null
            && $this->uploadedScanStatus === 'clean';
    }

    public function saveCertificate(): void
    {
        $this->validate(['expiry_date' => ['required', 'date', 'after:today']]);

        if (! $this->uploadedMediaId) {
            $this->addError('certificateFile', __('Please upload the certificate file.'));

            return;
        }

        $shop = Auth::user()->shop;

        $certificate = ShopCertificate::create([
            'shop_id' => $shop->id,
            'expiry_date' => $this->expiry_date,
            'media_id' => $this->uploadedMediaId,
            'status' => 'under_review',
            'is_verified' => false,
        ]);

        $admins = User::role('admin')->get();
        NotificationFacade::send($admins, new CertificateSubmittedNotification($certificate->load('shop')));

        unset($this->certificates);

        $this->showAddModal = false;
        $this->resetAddFields();

        Flux::toast(__('Certificate submitted for review.'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.company-hub.certificate-list');
    }

    private function resetAddFields(): void
    {
        $this->reset([
            'expiry_date',
            'certificateFile',
            'uploadedMediaId',
            'uploadedFilename',
            'uploadedScanStatus',
        ]);
        $this->resetErrorBag();
    }
}
