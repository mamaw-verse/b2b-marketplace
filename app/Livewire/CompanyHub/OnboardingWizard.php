<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Events\OnboardingSubmitted;
use App\Models\ShopOnboarding;
use App\Models\ShopOnboardingCertificate;
use App\Services\MediaUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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

    public int $currentStep = 1;

    public string $onboardingStatus = '';

    public ?string $adminNotes = null;

    /** @var array<string, mixed> */
    public array $formData = [];

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
            $this->onboardingStatus = $onboarding->status ?? '';
            $this->adminNotes = $onboarding->admin_notes;
            $this->formData = [
                'registered_business_name' => $onboarding->registered_business_name ?? '',
                'storefront_name' => $onboarding->storefront_name ?? '',
                'business_structure' => $onboarding->business_structure ?? '',
                'dti_sec_number' => $onboarding->dti_sec_number ?? '',
                'tax_identification_number' => $onboarding->tax_identification_number ?? '',
                'phone' => $onboarding->phone ?? '',
                'email' => $onboarding->email ?? '',
                'address_1' => $onboarding->address_1 ?? '',
                'address_2' => $onboarding->address_2 ?? '',
                'city' => $onboarding->city ?? '',
                'province' => $onboarding->province ?? '',
                'zip_code' => $onboarding->zip_code ?? '',
                'country_code' => $onboarding->country_code ?? 'PH',
                'product_categories' => $onboarding->product_categories ?? '',
                'fda_lto_number' => $onboarding->fda_lto_number ?? '',
                'average_production_lead_time' => $onboarding->average_production_lead_time ?? '',
                'export_readiness_status' => $onboarding->export_readiness_status ?? '',
                'value_chain_positions' => $onboarding->value_chain_positions ?? [],
                'capabilities' => $onboarding->capabilities ?? [],
            ];

            $this->certificates = $onboarding->certificates->map(fn (ShopOnboardingCertificate $cert) => [
                'id' => $cert->id,
                'certification_body' => $cert->certification_body,
                'certification_body_other' => $cert->certification_body_other ?? '',
                'registration_number' => $cert->registration_number,
                'expiry_date' => $cert->expiry_date?->format('Y-m-d') ?? '',
                'media_id' => $cert->media_id,
            ])->toArray();

            $this->loadUploadedDocuments($onboarding);
        } else {
            $this->initializeFormData();
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

    private function initializeFormData(): void
    {
        $this->formData = [
            'registered_business_name' => '',
            'storefront_name' => '',
            'business_structure' => '',
            'dti_sec_number' => '',
            'tax_identification_number' => '',
            'phone' => '',
            'email' => '',
            'address_1' => '',
            'address_2' => '',
            'city' => '',
            'province' => '',
            'zip_code' => '',
            'country_code' => 'PH',
            'product_categories' => '',
            'fda_lto_number' => '',
            'average_production_lead_time' => '',
            'export_readiness_status' => '',
            'value_chain_positions' => [],
            'capabilities' => [],
        ];
    }

    private function validateCurrentStep(): void
    {
        $rules = match ($this->currentStep) {
            1 => [
                'formData.registered_business_name' => ['required', 'string', 'max:255'],
                'formData.storefront_name' => ['required', 'string', 'max:255'],
                'formData.business_structure' => ['required', 'string'],
                'formData.dti_sec_number' => ['required', 'string', 'max:100'],
                'formData.tax_identification_number' => ['required', 'string', 'max:50'],
                'formData.phone' => ['nullable', 'string', 'max:30'],
                'formData.email' => ['nullable', 'email', 'max:255'],
                'formData.address_1' => ['nullable', 'string', 'max:255'],
                'formData.address_2' => ['nullable', 'string', 'max:255'],
                'formData.city' => ['nullable', 'string', 'max:120'],
                'formData.province' => ['nullable', 'string', 'max:120'],
                'formData.zip_code' => ['nullable', 'string', 'max:20'],
                'formData.country_code' => ['nullable', 'string', 'max:2'],
            ],
            2 => [
                'formData.product_categories' => ['nullable', 'string'],
                'formData.fda_lto_number' => ['nullable', 'string', 'max:100'],
                'formData.average_production_lead_time' => ['nullable', 'integer'],
                'formData.export_readiness_status' => ['required', 'string'],
            ],
            4 => [
                'formData.value_chain_positions' => ['array'],
                'formData.capabilities' => ['array'],
            ],
            default => [],
        };

        if (! empty($rules)) {
            $this->validate($rules);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getStepData(): array
    {
        return match ($this->currentStep) {
            1 => [
                'registered_business_name' => $this->formData['registered_business_name'] ?? '',
                'storefront_name' => $this->formData['storefront_name'] ?? '',
                'business_structure' => $this->formData['business_structure'] ?: null,
                'dti_sec_number' => $this->formData['dti_sec_number'] ?? '',
                'tax_identification_number' => $this->formData['tax_identification_number'] ?? '',
                'phone' => $this->formData['phone'] ?? '',
                'email' => $this->formData['email'] ?? '',
                'address_1' => $this->formData['address_1'] ?? '',
                'address_2' => $this->formData['address_2'] ?? '',
                'city' => $this->formData['city'] ?? '',
                'province' => $this->formData['province'] ?? '',
                'zip_code' => $this->formData['zip_code'] ?? '',
                'country_code' => $this->formData['country_code'] ?? 'PH',
            ],
            2 => [
                'product_categories' => $this->formData['product_categories'] ?? '',
                'fda_lto_number' => $this->formData['fda_lto_number'] ?? '',
                'average_production_lead_time' => $this->formData['average_production_lead_time'] ?: null,
                'export_readiness_status' => $this->formData['export_readiness_status'] ?: null,
            ],
            4 => [
                'value_chain_positions' => $this->formData['value_chain_positions'] ?? [],
                'capabilities' => $this->formData['capabilities'] ?? [],
            ],
            default => [],
        };
    }

    private function saveCertificates(ShopOnboarding $onboarding): void
    {
        $uploadService = app(MediaUploadService::class);

        foreach ($this->certificates as $index => $certData) {
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
