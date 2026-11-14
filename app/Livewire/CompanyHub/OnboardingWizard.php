<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Events\OnboardingSubmitted;
use App\Livewire\Forms\OnboardingForm;
use App\Models\Category;
use App\Models\Media;
use App\Models\ShopOnboarding;
use App\Models\ShopOnboardingCertificate;
use App\Services\MediaUploadService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Company Onboarding')]
final class OnboardingWizard extends Component
{
    use WithFileUploads;

    public const int TOTAL_STEPS = 4;

    public const array REQUIRED_DOCUMENTS = [
        'business_permit' => 'Business Permit',
    ];

    /**
     * Human-readable labels for validation error keys, used to render
     * friendly messages instead of raw dotted field paths.
     *
     * @var array<string, string>
     */
    public const array FIELD_LABELS = [
        'form.registered_business_name' => 'Registered Business Name',
        'form.company_description' => 'Company Description',
        'form.products_description' => 'Products Description',
        'form.business_structure' => 'Business Structure',
        'form.dti_sec_number' => 'DTI/SEC Number',
        'form.tax_identification_number' => 'TIN',
        'form.phone' => 'Phone',
        'form.email' => 'Email',
        'form.address_1' => 'Address Line 1',
        'form.address_2' => 'Address Line 2',
        'form.city' => 'City',
        'form.province' => 'Province',
        'form.zip_code' => 'Zip Code',
        'form.product_categories' => 'Product Categories',
        'form.fda_lto_number' => 'FDA LTO Number',
        'form.average_production_lead_time' => 'Average Production Lead Time',
        'form.export_readiness_status' => 'Export Readiness',
        'documentUploads.business_permit' => 'Business Permit',
    ];

    /**
     * Maps a validation error key (or its prefix) to the wizard step it
     * belongs to, so the error summary can offer a "jump to step" link.
     *
     * @var array<string, int>
     */
    public const array STEP_FOR_FIELD = [
        'form.registered_business_name' => 1,
        'form.company_description' => 1,
        'form.products_description' => 1,
        'form.business_structure' => 1,
        'form.dti_sec_number' => 1,
        'form.tax_identification_number' => 1,
        'form.phone' => 1,
        'form.email' => 1,
        'form.address_1' => 1,
        'form.address_2' => 1,
        'form.city' => 1,
        'form.province' => 1,
        'form.zip_code' => 1,
        'form.product_categories' => 2,
        'form.fda_lto_number' => 2,
        'form.average_production_lead_time' => 2,
        'form.export_readiness_status' => 2,
        'certificates' => 3,
        'documentUploads' => 4,
    ];

    #[Url]
    public int $currentStep = 1;

    public string $onboardingStatus = '';

    public ?string $adminNotes = null;

    public OnboardingForm $form;

    /** @var array<string, TemporaryUploadedFile|null> */
    public array $documentUploads = [];

    /** @var array<string, array<string, mixed>> */
    public array $uploadedDocuments = [];

    /**
     * Uploaded "Other Supporting Documents" (multi-file, optional).
     * Each entry: ['media_id' => int, 'filename' => string, 'scan_status' => string]
     *
     * @var array<int, array<string, mixed>>
     */
    public array $supportingDocuments = [];

    /** Temporary file slots for the multi-file supporting document dropzone. */
    public array $supportingDocumentUploads = [];

    /** @var array<int, array<string, mixed>> */
    public array $certificates = [];

    /** @var array<int, TemporaryUploadedFile|null> */
    public array $certificateFiles = [];

    public ?int $confirmingRemoveCertificateIndex = null;

    public function mount(): void
    {
        if (Auth::user()?->isShopStaff()) {
            session()->flash('error', __('Shop staff cannot apply for a separate business account.'));
            $this->redirect(route('portal.dashboard'), navigate: true);

            return;
        }

        // Suspended accounts may not access the onboarding wizard.
        $shop = Auth::user()?->shop;
        if ($shop?->status === 'suspended') {
            $this->redirect(route('portal.dashboard'), navigate: true);

            return;
        }

        // Clamp any URL-injected step to a valid range (e.g. stale bookmark).
        $this->currentStep = max(1, min($this->currentStep, self::TOTAL_STEPS));

        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        if ($onboarding) {

            $this->onboardingStatus = $onboarding->status ?? '';
            $this->adminNotes = $onboarding->admin_notes;
            $this->form->setOnboarding($onboarding);

            $this->certificates = $onboarding->certificates()->with('media')->get()->map(fn (ShopOnboardingCertificate $cert) => [
                'id' => $cert->id,
                'certification_body' => $cert->certification_body,
                'certification_body_other' => $cert->certification_body_other ?? '',
                'registration_number' => $cert->registration_number,
                'expiry_date' => $cert->expiry_date?->format('Y-m-d') ?? '',
                'media_id' => $cert->media_id,
                'filename' => $cert->media?->original_filename,
                'scan_status' => $cert->media?->scan_status,
            ])->toArray();

            $this->loadUploadedDocuments($onboarding);
        } else {
            $this->form->country_code = config('marketplace.instance_country', 'PH');
        }

        // Ensure at least one blank certificate fieldset is shown so the user
        // doesn't have to click "Add Certificate" for their first entry.
        if (empty($this->certificates)) {
            $this->addCertificate();
        }
    }

    public function nextStep(): void
    {
        $this->saveCurrentStep();

        if ($this->currentStep < self::TOTAL_STEPS) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Jump directly to a step. Used by the validation-error summary so the
     * user can navigate to the field a hidden error refers to. Does not
     * re-run/save the current step, since the intent is to correct errors.
     */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= self::TOTAL_STEPS) {
            $this->currentStep = $step;
        }
    }

    public function saveCurrentStep(): void
    {
        $this->validateCurrentStep();

        $onboarding = ShopOnboarding::updateOrCreate(
            ['user_id' => Auth::id()],
            $this->getStepData(),
        );

        if ($this->currentStep === 3) {
            $this->saveCertificates($onboarding);
        }

        if ($this->currentStep === 4) {
            $this->processDocumentUploads($onboarding);
        }
    }

    public function submit(): void
    {
        // Only new/pending/revision/rejected applications may be (re)submitted.
        // 'submitted' is awaiting review; 'approved' is final.
        if (! in_array($this->onboardingStatus, ['', 'pending', 'requires_revision', 'rejected'], true)) {
            return;
        }

        $this->saveCurrentStep();

        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        // Reload fresh document scan statuses from DB
        $this->loadUploadedDocuments($onboarding);

        $documents = $onboarding->documents()->get();

        // Find which required documents are missing entirely
        $uploadedTypes = $documents->pluck('description')->filter()->values()->toArray();

        $hasErrors = false;

        // Clear any stale per-type document errors before re-evaluating.
        foreach (array_keys(self::REQUIRED_DOCUMENTS) as $type) {
            $this->resetErrorBag("documentUploads.{$type}");
        }

        $this->resetErrorBag('documents');
        $this->resetErrorBag('supportingDocuments');
        $this->resetErrorBag('supportingDocumentUploads');

        foreach (self::REQUIRED_DOCUMENTS as $type => $label) {
            if (! in_array($type, $uploadedTypes, true)) {
                $this->addError("documentUploads.{$type}", "{$label} is required.");
                $hasErrors = true;
            }
        }

        foreach ($documents as $doc) {
            $type = $doc->description;
            $label = self::REQUIRED_DOCUMENTS[$type] ?? $type;

            if (! (bool) config('services.clamav.enabled', false)) {
                continue;
            }

            if ($doc->scan_status === 'pending') {
                $this->addError("documentUploads.{$type}", "{$label}: Security scan in progress — please wait and try again.");
                $hasErrors = true;
            } elseif ($doc->scan_status === 'infected') {
                $this->addError("documentUploads.{$type}", "{$label}: Failed security scan — please re-upload a clean file.");
                $hasErrors = true;
            }
        }

        // Supporting documents are optional but must not have pending/infected scans.
        foreach ($this->supportingDocuments as $index => $doc) {
            if (! (bool) config('services.clamav.enabled', false)) {
                break;
            }
            if (($doc['scan_status'] ?? null) === 'pending') {
                $this->addError('supportingDocuments', 'A supporting document scan is still in progress — please wait and try again.');
                $hasErrors = true;
                break;
            }
            if (($doc['scan_status'] ?? null) === 'infected') {
                $this->addError('supportingDocuments', 'A supporting document failed its security scan — please remove it and upload a clean file.');
                $hasErrors = true;
                break;
            }
        }

        if ($hasErrors) {
            return;
        }

        $onboarding->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        OnboardingSubmitted::dispatch($onboarding);

        session()->flash('message', 'Your onboarding application has been submitted for review.');

        $this->redirect(route('account.onboarding'));
    }

    public function addCertificate(): void
    {
        $this->certificates[] = [
            'id' => null,
            'certification_body' => '',
            'certification_body_other' => '',
            'registration_number' => '',
            'expiry_date' => '',
            'media_id' => null,
            'filename' => null,
            'scan_status' => null,
        ];
    }

    public function confirmRemoveCertificate(int $index): void
    {
        $this->confirmingRemoveCertificateIndex = $index;
        Flux::modal('confirm-remove-certificate')->show();
    }

    public function removeCertificate(?int $index = null): void
    {
        $index = $index ?? $this->confirmingRemoveCertificateIndex;

        if ($index === null) {
            return;
        }

        $certificate = $this->certificates[$index] ?? null;

        if ($certificate && $certificate['id']) {
            ShopOnboardingCertificate::destroy($certificate['id']);
        }

        unset($this->certificates[$index], $this->certificateFiles[$index]);
        $this->certificates = array_values($this->certificates);
        $this->certificateFiles = array_values($this->certificateFiles);

        $this->confirmingRemoveCertificateIndex = null;
    }

    public function uploadDocument(string $documentType, MediaUploadService $uploadService): void
    {
        $file = $this->documentUploads[$documentType] ?? null;

        if (! $file) {
            return;
        }

        try {
            $media = $uploadService->store($file, Auth::id(), $documentType);
        } catch (Throwable $e) {
            $this->addError("documentUploads.{$documentType}", $e->getMessage());

            return;
        }

        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        if ($onboarding) {
            $onboarding->documents()->syncWithoutDetaching([$media->id]);
        }

        $this->uploadedDocuments[$documentType] = [
            'media_id' => $media->id,
            'filename' => $media->original_filename,
            'scan_status' => $media->scan_status,
        ];

        unset($this->documentUploads[$documentType]);
    }

    /**
     * Livewire lifecycle hook: automatically upload a document as soon as a
     * file is selected via the wire:model file input, removing the need
     * for the user to click an "Upload" button.
     */
    public function updatedDocumentUploads(mixed $value, string $key): void
    {
        // Clear any prior error for this slot the moment the user picks a new file.
        $this->resetErrorBag("documentUploads.{$key}");

        if ($value !== null) {
            $this->uploadDocument($key, app(MediaUploadService::class));
        }
    }

    /**
     * Livewire lifecycle hook: when a certificate file is selected via the
     * dropzone wire:model, immediately persist it by uploading to S3 and
     * attaching it to the certificate entry — no manual "Next" click needed.
     */
    public function updatedCertificateFiles(mixed $value, string $key): void
    {
        if ($value === null) {
            return;
        }

        // Clear the prior file-required error as soon as the user picks a file.
        $this->resetErrorBag("certificateFiles.{$key}");

        $index = (int) $key;
        $certificate = $this->certificates[$index] ?? null;

        if ($certificate === null) {
            return;
        }

        $uploadService = app(MediaUploadService::class);

        try {
            $media = $uploadService->store($value, Auth::id(), 'halal_certificate');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            $this->addError("certificateFiles.{$index}", $message);

            return;
        } catch (Throwable $e) {
            $this->addError("certificateFiles.{$index}", $e->getMessage());

            return;
        }

        $this->certificates[$index]['media_id'] = $media->id;
        $this->certificates[$index]['filename'] = $media->original_filename;
        $this->certificates[$index]['scan_status'] = $media->scan_status;
        unset($this->certificateFiles[$index]);

        // Persist to DB immediately so the upload survives a page refresh.
        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        if ($onboarding && $certificate['id']) {
            ShopOnboardingCertificate::where('id', $certificate['id'])->update(['media_id' => $media->id]);
        }
    }

    /**
     * General lifecycle hook: clear the validation error for any form field
     * or nested certificate field the moment the user makes a change, so the
     * error ring and message disappear as soon as the correction is provided.
     */
    public function updated(string $key): void
    {
        // form.* fields map directly to their error key.
        if (str_starts_with($key, 'form.')) {
            $this->resetErrorBag($key);

            return;
        }

        // certificates.N.field — clear the field-level error.
        if (str_starts_with($key, 'certificates.')) {
            $this->resetErrorBag($key);
        }
    }

    /**
     * Clear an already-uploaded certificate file so the user can replace it
     * with a fresh dropzone. Only clears local state; the media record is kept
     * until the step is saved (avoiding orphaned uploads on cancel).
     */
    public function replaceCertificateFile(int $index): void
    {
        if (! isset($this->certificates[$index])) {
            return;
        }

        $this->certificates[$index]['media_id'] = null;
        $this->certificates[$index]['filename'] = null;
        $this->certificates[$index]['scan_status'] = null;
        unset($this->certificateFiles[$index]);
    }

    /**
     * Detach an infected or unwanted certificate file so the user can
     * re-upload a clean replacement. Nulls out the media_id on the DB record.
     */
    public function resetCertificateFile(int $index): void
    {
        if (! isset($this->certificates[$index])) {
            return;
        }

        $certId = $this->certificates[$index]['id'] ?? null;

        if ($certId) {
            ShopOnboardingCertificate::where('id', $certId)->update(['media_id' => null]);
        }

        $this->certificates[$index]['media_id'] = null;
        $this->certificates[$index]['filename'] = null;
        $this->certificates[$index]['scan_status'] = null;
        unset($this->certificateFiles[$index]);
    }

    public function refreshDocumentStatuses(): void
    {
        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        if ($onboarding) {
            $this->loadUploadedDocuments($onboarding);
        }
    }

    /**
     * Detach an uploaded document so the seller can re-upload it.
     * Used when a scan is stuck in "pending" or otherwise needs replacing.
     */
    public function resetDocument(string $documentType): void
    {
        if (! array_key_exists($documentType, self::REQUIRED_DOCUMENTS)) {
            return;
        }

        $mediaId = $this->uploadedDocuments[$documentType]['media_id'] ?? null;

        if ($mediaId) {
            $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();
            $onboarding?->documents()->detach($mediaId);
        }

        unset($this->uploadedDocuments[$documentType]);
        unset($this->documentUploads[$documentType]);
    }

    /**
     * Auto-upload supporting documents after the Livewire temp-upload finishes.
     * Called via x-on:livewire-upload-finish on the dropzone.
     */
    public function uploadSupportingDocuments(MediaUploadService $uploadService): void
    {
        $this->resetErrorBag('supportingDocumentUploads');

        if (empty($this->supportingDocumentUploads)) {
            return;
        }

        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        foreach ($this->supportingDocumentUploads as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            try {
                $media = $uploadService->store($file, Auth::id(), 'other_supporting_document');
            } catch (Throwable $e) {
                $this->addError('supportingDocumentUploads', $e->getMessage());

                continue;
            }

            if ($onboarding) {
                $onboarding->documents()->syncWithoutDetaching([$media->id]);
            }

            $this->supportingDocuments[] = [
                'media_id' => $media->id,
                'filename' => $media->original_filename,
                'scan_status' => $media->scan_status,
            ];
        }

        $this->reset('supportingDocumentUploads');
    }

    /**
     * Remove a single supporting document by its index.
     * Detaches the media record from the onboarding so it no longer counts.
     */
    public function removeSupportingDocument(int $index): void
    {
        $doc = $this->supportingDocuments[$index] ?? null;

        if (! $doc) {
            return;
        }

        $mediaId = $doc['media_id'] ?? null;

        if ($mediaId) {
            $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();
            $onboarding?->documents()->detach($mediaId);
        }

        unset($this->supportingDocuments[$index]);
        $this->supportingDocuments = array_values($this->supportingDocuments);
    }

    /**
     * Re-open a rejected application for editing so the seller can correct it
     * and resubmit. Moves the record back to the editable "pending" state.
     */
    public function reapply(): void
    {
        if ($this->onboardingStatus !== 'rejected') {
            return;
        }

        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        if (! $onboarding) {
            return;
        }

        // Detach all documents and delete all certificates.
        $onboarding->documents()->detach();
        $onboarding->certificates()->delete();

        // Clear all form fields on the DB record.
        $onboarding->update([
            'status' => 'pending',
            'admin_notes' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by_id' => null,
            'registered_business_name' => '',
            'business_structure' => 'sole_proprietorship',
            'company_description' => null,
            'products_description' => null,
            'dti_sec_number' => null,
            'tax_identification_number' => null,
            'phone' => null,
            'email' => null,
            'address_1' => null,
            'address_2' => null,
            'city' => null,
            'province' => null,
            'zip_code' => null,
            'product_categories' => null,
            'fda_lto_number' => null,
            'average_production_lead_time' => null,
            'export_readiness_status' => 'never_exported',
        ]);

        // Reset all local component state.
        $this->form->setOnboarding($onboarding->fresh());
        $this->certificates = [];
        $this->certificateFiles = [];
        $this->uploadedDocuments = [];
        $this->documentUploads = [];
        $this->supportingDocuments = [];
        $this->supportingDocumentUploads = [];
        $this->onboardingStatus = 'pending';
        $this->adminNotes = null;
        $this->currentStep = 1;
    }

    /**
     * Selectable categories grouped under their top-level parent groups,
     * for the step 2 product-category checkbox group.
     * A group with no children is itself selectable and is listed alone.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryGroups(): Collection
    {
        return Category::with('children')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Expose the field-label map to the view for readable error messages.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function fieldLabels(): array
    {
        return self::FIELD_LABELS;
    }

    /**
     * Contact email shown in the "Revision Required" banner.
     * Reads from marketplace.support_email, falling back to mail.from.address.
     */
    #[Computed]
    public function adminEmail(): string
    {
        return config('marketplace.support_email', config('mail.from.address', ''));
    }

    /**
     * Whether any uploaded document is still awaiting its security scan.
     * Drives the checklist's auto-refresh: the view polls only while this is
     * true, and stops once every document is clean/infected (settled).
     */
    #[Computed]
    public function hasPendingDocuments(): bool
    {
        if (! (bool) config('services.clamav.enabled', false)) {
            return false;
        }

        foreach ($this->uploadedDocuments as $document) {
            if (($document['scan_status'] ?? null) === 'pending') {
                return true;
            }
        }

        foreach ($this->supportingDocuments as $document) {
            if (($document['scan_status'] ?? null) === 'pending') {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether any uploaded certificate file is still awaiting its security scan.
     * Drives auto-refresh polling on the certificates step.
     */
    #[Computed]
    public function hasPendingCertificates(): bool
    {
        if (! (bool) config('services.clamav.enabled', false)) {
            return false;
        }

        foreach ($this->certificates as $certificate) {
            if (($certificate['scan_status'] ?? null) === 'pending') {
                return true;
            }
        }

        return false;
    }

    public function refreshCertificateStatuses(): void
    {
        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        if (! $onboarding) {
            return;
        }

        $mediaIds = collect($this->certificates)
            ->pluck('media_id')
            ->filter()
            ->values()
            ->all();

        if (empty($mediaIds)) {
            return;
        }

        $scanStatuses = Media::whereIn('id', $mediaIds)
            ->pluck('scan_status', 'id');

        foreach ($this->certificates as $index => $certificate) {
            $mediaId = $certificate['media_id'] ?? null;

            if ($mediaId && isset($scanStatuses[$mediaId])) {
                $this->certificates[$index]['scan_status'] = $scanStatuses[$mediaId];
            }
        }
    }

    /**
     * Resolve the wizard step number for a given validation error key.
     * Falls back to the key's prefix (e.g. "certificates.0.body" → "certificates")
     * and returns null when the key can't be mapped to a step.
     */
    public function stepForErrorKey(string $errorKey): ?int
    {
        if (isset(self::STEP_FOR_FIELD[$errorKey])) {
            return self::STEP_FOR_FIELD[$errorKey];
        }

        $prefix = Str::before($errorKey, '.');

        return self::STEP_FOR_FIELD[$prefix] ?? null;
    }

    public function render()
    {
        return view('livewire.company-hub.onboarding-wizard');
    }

    /**
     * Handle realtime onboarding status change broadcast from admin.
     * Listens on private channel: user.{id}
     *
     * @param  array<string, mixed>  $data
     */
    #[On('echo-private:user.{userId},onboarding.status.changed')]
    public function onStatusChanged(array $data): void
    {
        $this->onboardingStatus = $data['status'] ?? $this->onboardingStatus;
        $this->adminNotes = $data['admin_notes'] ?? null;
    }

    /**
     * Get the user ID for the private channel listener.
     */
    public function getUserIdProperty(): int
    {
        return Auth::id();
    }

    private function validateCurrentStep(): void
    {
        $rules = collect($this->form->rulesForStep($this->currentStep))
            ->mapWithKeys(fn ($rule, $field) => ["form.{$field}" => $rule])
            ->all();

        if (! empty($rules)) {
            $this->validate($rules, [], self::FIELD_LABELS);
        }
    }

    /**
     * Build validation rules and per-index messages for step 3 certificates.
     * Each certificate must have a certification body, and either an existing
     * uploaded file (media_id) or a freshly selected upload.
     *
     * @return array{0: array<string, array<int, string>>, 1: array<string, string>}
     */

    /**
     * @return array<string, mixed>
     */
    private function getStepData(): array
    {
        return $this->form->dataForStep($this->currentStep);
    }

    private function saveCertificates(ShopOnboarding $onboarding): void
    {
        foreach ($this->certificates as $index => $data) {
            // Skip completely blank rows — no expiry and no file means nothing to save.
            if (empty($data['expiry_date']) && empty($data['media_id'])) {
                continue;
            }

            $attributes = [
                'onboarding_id' => $onboarding->id,
                'certification_body' => $data['certification_body'] ?: null,
                'certification_body_other' => $data['certification_body_other'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?: null,
                'media_id' => $data['media_id'] ?? null,
            ];

            if (! empty($data['id'])) {
                ShopOnboardingCertificate::where('id', $data['id'])->update($attributes);
            } else {
                $cert = ShopOnboardingCertificate::create($attributes);
                $this->certificates[$index]['id'] = $cert->id;
            }
        }
    }

    private function processDocumentUploads(ShopOnboarding $onboarding): void
    {
        $uploadService = app(MediaUploadService::class);
        foreach ($this->documentUploads as $documentType => $file) {
            if ($file) {
                $this->uploadDocument($documentType, $uploadService);
            }
        }
    }

    private function loadUploadedDocuments(ShopOnboarding $onboarding): void
    {
        $this->supportingDocuments = [];

        foreach ($onboarding->documents as $media) {
            $documentType = $media->description;

            if ($documentType && array_key_exists($documentType, self::REQUIRED_DOCUMENTS)) {
                $this->uploadedDocuments[$documentType] = [
                    'media_id' => $media->id,
                    'filename' => $media->original_filename,
                    'scan_status' => $media->scan_status,
                ];
            } elseif ($documentType === 'other_supporting_document') {
                $this->supportingDocuments[] = [
                    'media_id' => $media->id,
                    'filename' => $media->original_filename,
                    'scan_status' => $media->scan_status,
                ];
            }
        }
    }
}
