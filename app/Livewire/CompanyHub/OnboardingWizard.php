<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Events\OnboardingSubmitted;
use App\Livewire\Forms\OnboardingForm;
use App\Models\Category;
use App\Models\ShopOnboarding;
use App\Models\ShopOnboardingCertificate;
use App\Services\MediaUploadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Company Onboarding')]
final class OnboardingWizard extends Component
{
    use WithFileUploads;

    public const int TOTAL_STEPS = 5;

    public const array REQUIRED_DOCUMENTS = [
        'dti_sec_certificate' => 'DTI/SEC Certificate',
        'business_permit' => 'Business Permit',
        'bir_form_2303' => 'BIR Form 2303',
        'ingredient_list' => 'Ingredient List',
    ];

    /**
     * Human-readable labels for validation error keys, used to render
     * friendly messages instead of raw dotted field paths.
     *
     * @var array<string, string>
     */
    public const array FIELD_LABELS = [
        'form.registered_business_name' => 'Registered Business Name',
        'form.storefront_name' => 'Storefront Name',
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
        'form.value_chain_positions' => 'Value Chain Positions',
        'form.capabilities' => 'Capabilities',
        'documents' => 'Required Documents',
    ];

    public int $currentStep = 1;

    public string $onboardingStatus = '';

    public ?string $adminNotes = null;

    public OnboardingForm $form;

    /** @var array<int, array<string, mixed>> */
    public array $certificates = [];

    public ?int $confirmingRemoveCertificateIndex = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null> */
    public array $certificateFiles = [];

    /** @var array<string, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null> */
    public array $documentUploads = [];

    /** @var array<string, array<string, mixed>> */
    public array $uploadedDocuments = [];

    public function mount(): void
    {
        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        if ($onboarding) {
            // Already approved: the wizard is read-only/complete — send to the
            // dashboard rather than letting the seller re-edit an approved shop.
            if ($onboarding->status === 'approved') {
                $this->redirect(route('hub.hub-dashboard'));

                return;
            }

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
            ])->toArray();

            $this->loadUploadedDocuments($onboarding);
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

        if ($this->currentStep === 5) {
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
        $missingLabels = [];
        $pendingLabels = [];
        $infectedLabels = [];

        foreach (self::REQUIRED_DOCUMENTS as $type => $label) {
            if (! in_array($type, $uploadedTypes, true)) {
                $missingLabels[] = $label;
            }
        }

        foreach ($documents as $doc) {
            $label = self::REQUIRED_DOCUMENTS[$doc->description] ?? $doc->description;
            if ($doc->scan_status === 'pending') {
                $pendingLabels[] = $label;
            } elseif ($doc->scan_status === 'infected') {
                $infectedLabels[] = $label;
            }
        }

        $errors = [];

        if (! empty($missingLabels)) {
            $errors[] = 'Missing: '.implode(', ', $missingLabels).'.';
        }

        if (! empty($pendingLabels)) {
            $errors[] = 'Still scanning (please wait and try again): '.implode(', ', $pendingLabels).'.';
        }

        if (! empty($infectedLabels)) {
            $errors[] = 'Failed security scan (please re-upload): '.implode(', ', $infectedLabels).'.';
        }

        if (! empty($errors)) {
            $this->addError('documents', implode(' ', $errors));

            return;
        }

        $onboarding->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        OnboardingSubmitted::dispatch($onboarding);

        session()->flash('message', 'Your onboarding application has been submitted for review.');

        $this->redirect(route('hub.onboarding'));
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
        ];
    }

    public function confirmRemoveCertificate(int $index): void
    {
        $this->confirmingRemoveCertificateIndex = $index;
        \Flux\Flux::modal('confirm-remove-certificate')->show();
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
     * Re-open a rejected application for editing so the seller can correct it
     * and resubmit. Moves the record back to the editable "pending" state.
     */
    public function reapply(): void
    {
        if ($this->onboardingStatus !== 'rejected') {
            return;
        }

        $onboarding = ShopOnboarding::where('user_id', Auth::id())->first();

        $onboarding?->update([
            'status' => 'pending',
            'admin_notes' => null,
        ]);

        $this->onboardingStatus = 'pending';
        $this->adminNotes = null;
        $this->currentStep = 1;
    }

    /**
     * Categories available for the step 2 product-category checkbox group.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('name')->get();
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

        if ($this->currentStep === 3) {
            [$certRules, $certMessages] = $this->certificateValidation();

            if (! empty($certRules)) {
                $this->validate($certRules, $certMessages);
            }
        }

        if (! empty($rules)) {
            $this->validate($rules);
        }
    }

    /**
     * Build validation rules and per-index messages for step 3 certificates.
     * Each certificate must have a certification body, and either an existing
     * uploaded file (media_id) or a freshly selected upload.
     *
     * @return array{0: array<string, array<int, string>>, 1: array<string, string>}
     */
    private function certificateValidation(): array
    {
        $rules = [];
        $messages = [];

        foreach ($this->certificates as $index => $certificate) {
            $rules["certificates.{$index}.certification_body"] = ['required', 'string'];
            $messages["certificates.{$index}.certification_body.required"] = "Certificate #{$index}: Certification body is required.";

            if (empty($certificate['media_id'])) {
                $rules["certificateFiles.{$index}"] = ['required'];
                $messages["certificateFiles.{$index}.required"] = 'Certificate #'.($index + 1).': A certificate file is required.';
            }

            // Fix the human index in the certification-body message (1-based).
            $messages["certificates.{$index}.certification_body.required"] = 'Certificate #'.($index + 1).': Certification body is required.';
        }

        return [$rules, $messages];
    }

    /**
     * @return array<string, mixed>
     */
    private function getStepData(): array
    {
        return $this->form->dataForStep($this->currentStep);
    }

    private function saveCertificates(ShopOnboarding $onboarding): void
    {
        $uploadService = app(MediaUploadService::class);

        foreach ($this->certificates as $index => $certData) {
            // Guard: never persist a certificate without a certification body.
            if (empty($certData['certification_body'])) {
                continue;
            }

            $mediaId = $certData['media_id'];

            if (isset($this->certificateFiles[$index]) && $this->certificateFiles[$index]) {
                $file = $this->certificateFiles[$index];

                try {
                    $media = $uploadService->store($file, Auth::id(), 'halal_certificate');
                } catch (ValidationException $e) {
                    $message = collect($e->errors())->flatten()->first() ?? $e->getMessage();
                    $this->addError("certificateFiles.{$index}", $message);

                    continue;
                } catch (Throwable $e) {
                    $this->addError("certificateFiles.{$index}", $e->getMessage());

                    continue;
                }

                $mediaId = $media->id;
                $this->certificates[$index]['media_id'] = $media->id;
                unset($this->certificateFiles[$index]);
            }

            $attributes = [
                'onboarding_id' => $onboarding->id,
                'certification_body' => $certData['certification_body'] ?: null,
                'certification_body_other' => $certData['certification_body_other'] ?: null,
                'registration_number' => $certData['registration_number'] ?? '',
                'expiry_date' => $certData['expiry_date'] ?: null,
                'media_id' => $mediaId,
            ];

            if ($certData['id']) {
                ShopOnboardingCertificate::where('id', $certData['id'])->update($attributes);
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
        foreach ($onboarding->documents as $media) {
            $documentType = $media->description;

            if ($documentType && array_key_exists($documentType, self::REQUIRED_DOCUMENTS)) {
                $this->uploadedDocuments[$documentType] = [
                    'media_id' => $media->id,
                    'filename' => $media->original_filename,
                    'scan_status' => $media->scan_status,
                ];
            }
        }
    }
}
