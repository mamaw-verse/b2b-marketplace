<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\OnboardingWizard;
use App\Models\ShopOnboarding;
use App\Models\ShopOnboardingCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Storage::fake('s3');
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->onboarding = ShopOnboarding::create([
        'user_id' => $this->user->id,
        'registered_business_name' => 'Test Co',
        'storefront_name' => 'Test Store',
        'business_structure' => 'sole_proprietorship',
        'dti_sec_number' => 'DTI-001',
        'tax_identification_number' => '123-456',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'value_chain_positions' => [],
        'capabilities' => [],
    ]);
});

test('certificate step renders on step 3', function () {
    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->assertSeeHtml('Halal Certification');
});

test('can add a certificate and upload a valid PDF', function () {
    $file = UploadedFile::fake()->create('halal-cert.pdf', 500, 'application/pdf');

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('addCertificate')
        ->set('certificates.0.certification_body', 'IDCP')
        ->set('certificates.0.registration_number', 'IDCP-2026-00931')
        ->set('certificates.0.expiry_date', now()->addYear()->format('Y-m-d'))
        ->set('certificateFiles.0', $file)
        ->call('saveCurrentStep')
        ->assertHasNoErrors();

    expect(ShopOnboardingCertificate::count())->toBe(1);
    expect(ShopOnboardingCertificate::first()->registration_number)->toBe('IDCP-2026-00931');
    Storage::disk('s3')->assertExists(
        ShopOnboardingCertificate::first()->media->storage_path
    );
});

test('can upload a valid JPEG certificate file', function () {
    $file = UploadedFile::fake()->image('halal-cert.jpg');

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('addCertificate')
        ->set('certificates.0.certification_body', 'HDIP')
        ->set('certificates.0.registration_number', 'HDIP-001')
        ->set('certificates.0.expiry_date', now()->addYear()->format('Y-m-d'))
        ->set('certificateFiles.0', $file)
        ->call('saveCurrentStep')
        ->assertHasNoErrors();

    expect(ShopOnboardingCertificate::count())->toBe(1);
});

test('rejects a certificate file that exceeds the 20MB size limit', function () {
    $file = UploadedFile::fake()->create('huge-cert.pdf', 21 * 1024, 'application/pdf');

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('addCertificate')
        ->set('certificates.0.certification_body', 'IDCP')
        ->set('certificates.0.registration_number', 'IDCP-001')
        ->set('certificates.0.expiry_date', now()->addYear()->format('Y-m-d'))
        ->set('certificateFiles.0', $file)
        ->call('saveCurrentStep')
        ->assertHasErrors(['certificateFiles.0']);

    expect(ShopOnboardingCertificate::count())->toBe(1);
});

test('rejects a certificate file with a disallowed MIME type', function () {
    $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('addCertificate')
        ->set('certificates.0.certification_body', 'IDCP')
        ->set('certificates.0.registration_number', 'IDCP-001')
        ->set('certificates.0.expiry_date', now()->addYear()->format('Y-m-d'))
        ->set('certificateFiles.0', $file)
        ->call('saveCurrentStep')
        ->assertHasErrors(['certificateFiles.0']);

    expect(ShopOnboardingCertificate::count())->toBe(0);
});

test('can save certificate fields without uploading a file', function () {
    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('addCertificate')
        ->set('certificates.0.certification_body', 'IDCP')
        ->set('certificates.0.registration_number', 'IDCP-2026-00931')
        ->set('certificates.0.expiry_date', now()->addYear()->format('Y-m-d'))
        ->call('saveCurrentStep')
        ->assertHasNoErrors();

    expect(ShopOnboardingCertificate::count())->toBe(1);
    expect(ShopOnboardingCertificate::first()->media_id)->toBeNull();
});

test('can remove a certificate', function () {
    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('addCertificate')
        ->set('certificates.0.certification_body', 'IDCP')
        ->set('certificates.0.registration_number', 'IDCP-001')
        ->set('certificates.0.expiry_date', now()->addYear()->format('Y-m-d'))
        ->call('saveCurrentStep')
        ->call('removeCertificate', 0)
        ->assertSet('certificates', []);

    expect(ShopOnboardingCertificate::count())->toBe(0);
});

test('loads existing certificates on mount', function () {
    $this->onboarding->certificates()->create([
        'certification_body' => 'IDCP',
        'registration_number' => 'IDCP-LOADED-001',
        'expiry_date' => now()->addYear(),
        'media_id' => null,
    ]);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->assertSet('certificates.0.registration_number', 'IDCP-LOADED-001');
});

test('nextStep advances from step 3 to step 4 after saving certificates', function () {
    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('nextStep')
        ->assertSet('currentStep', 4);
});
