<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\OnboardingWizard;
use App\Models\Media;
use App\Models\ShopOnboarding;
use App\Models\ShopOnboardingCertificate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('wizard mounts with empty form object for new onboarding', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->assertSet('currentStep', 1)
        ->assertSet('form.registered_business_name', '')
        ->assertSet('form.country_code', 'PH');
});

test('wizard hydrates form object from existing onboarding', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Acme Foods Inc',
        'storefront_name' => 'Acme Halal',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-12345',
        'tax_identification_number' => 'TIN-98765',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'pending',
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->assertSet('form.registered_business_name', 'Acme Foods Inc')
        ->assertSet('form.storefront_name', 'Acme Halal')
        ->assertSet('form.business_structure', 'corporation')
        ->assertSet('form.dti_sec_number', 'SEC-12345');
});

test('step 1 validation fails when required fields are empty', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('form.registered_business_name', '')
        ->set('form.storefront_name', '')
        ->call('nextStep')
        ->assertHasErrors([
            'form.registered_business_name' => 'required',
            'form.storefront_name' => 'required',
        ])
        ->assertSet('currentStep', 1);
});

test('step 1 advances to step 2 when valid', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('form.registered_business_name', 'Valid Business')
        ->set('form.storefront_name', 'Valid Storefront')
        ->set('form.business_structure', 'sole_proprietorship')
        ->set('form.dti_sec_number', 'DTI-111')
        ->set('form.tax_identification_number', 'TIN-222')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 2);
});

test('step 1 persists form data to the database', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('form.registered_business_name', 'Persisted Co')
        ->set('form.storefront_name', 'Persisted Shop')
        ->set('form.business_structure', 'partnership')
        ->set('form.dti_sec_number', 'DTI-333')
        ->set('form.tax_identification_number', 'TIN-444')
        ->call('nextStep');

    $this->assertDatabaseHas('shop_onboardings', [
        'user_id' => $seller->id,
        'registered_business_name' => 'Persisted Co',
        'storefront_name' => 'Persisted Shop',
        'business_structure' => 'partnership',
    ]);
});

test('certificate filename is hydrated and displayed with its extension preserved', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Cert Co',
        'storefront_name' => 'Cert Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-1',
        'tax_identification_number' => 'TIN-1',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'requires_revision',
    ]);

    $longName = 'a-very-long-halal-certification-document-name-2026.pdf';
    $media = Media::factory()->create([
        'original_filename' => $longName,
        'extension' => 'pdf',
        'uploaded_by_id' => $seller->id,
    ]);

    ShopOnboardingCertificate::create([
        'onboarding_id' => $onboarding->id,
        'certification_body' => 'IDCP',
        'registration_number' => 'REG-1',
        'expiry_date' => now()->addYear()->format('Y-m-d'),
        'media_id' => $media->id,
    ]);

    $this->actingAs($seller);

    $component = Livewire::test(OnboardingWizard::class);

    // Filename is hydrated into the certificate array.
    expect($component->get('certificates')[0]['filename'])->toBe($longName);

    // Rendered display truncates the base name but keeps the .pdf extension.
    $component->set('currentStep', 3)
        ->assertSee('.pdf')
        ->assertSee('…');
});

test('resetDocument detaches the media and clears the stuck document', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Doc Co',
        'storefront_name' => 'Doc Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-2',
        'tax_identification_number' => 'TIN-2',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'requires_revision',
    ]);

    $media = Media::factory()->create([
        'original_filename' => 'permit.pdf',
        'extension' => 'pdf',
        'scan_status' => 'pending',
        'description' => 'business_permit',
        'uploaded_by_id' => $seller->id,
    ]);

    $onboarding->documents()->attach($media->id);

    $this->actingAs($seller);

    $component = Livewire::test(OnboardingWizard::class);

    // The pending document is loaded.
    expect($component->get('uploadedDocuments'))->toHaveKey('business_permit');

    $component->call('resetDocument', 'business_permit');

    // Local state cleared and pivot detached.
    expect($component->get('uploadedDocuments'))->not->toHaveKey('business_permit');
    $this->assertDatabaseMissing('shop_onboarding_documents', [
        'onboarding_id' => $onboarding->id,
        'media_id' => $media->id,
    ]);
});

test('resetDocument ignores unknown document types', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Doc Co',
        'storefront_name' => 'Doc Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-3',
        'tax_identification_number' => 'TIN-3',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'requires_revision',
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->call('resetDocument', 'not_a_real_type')
        ->assertHasNoErrors();
});

test('step 2 stores selected product categories as a comma-separated list', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $catA = App\Models\Category::factory()->create(['name' => 'Bakery']);
    $catB = App\Models\Category::factory()->create(['name' => 'Spices']);

    ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Cat Store Co',
        'storefront_name' => 'Cat Store Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-CS',
        'tax_identification_number' => 'TIN-CS',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'requires_revision',
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 2)
        ->set('form.product_categories', [$catA->id, $catB->id])
        ->set('form.export_readiness_status', 'never_exported')
        ->call('saveCurrentStep')
        ->assertHasNoErrors();

    $onboarding = ShopOnboarding::where('user_id', $seller->id)->first();

    expect($onboarding->product_categories)->toBe("{$catA->id},{$catB->id}");
});

test('step 2 hydrates stored categories back into an array', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $catA = App\Models\Category::factory()->create();
    $catB = App\Models\Category::factory()->create();

    ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Cat Co',
        'storefront_name' => 'Cat Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-C',
        'tax_identification_number' => 'TIN-C',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'product_categories' => "{$catA->id},{$catB->id}",
        'status' => 'requires_revision',
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->assertSet('form.product_categories', [$catA->id, $catB->id]);
});

test('step 1 hard-codes country to PH regardless of prior value', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('form.registered_business_name', 'Country Co')
        ->set('form.storefront_name', 'Country Shop')
        ->set('form.business_structure', 'corporation')
        ->set('form.dti_sec_number', 'DTI-C')
        ->set('form.tax_identification_number', 'TIN-C')
        ->call('nextStep');

    $this->assertDatabaseHas('shop_onboardings', [
        'user_id' => $seller->id,
        'country_code' => 'PH',
    ]);
});

test('step 3 requires certification body for each certificate', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('addCertificate')
        ->call('saveCurrentStep')
        ->assertHasErrors(['certificates.0.certification_body']);
});

test('step 3 requires a file when the certificate has no existing media', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('addCertificate')
        ->set('certificates.0.certification_body', 'IDCP')
        ->call('saveCurrentStep')
        ->assertHasErrors(['certificateFiles.0']);
});

test('reapply moves a rejected application back to pending and resets to step 1', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Rejected Co',
        'storefront_name' => 'Rejected Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-R',
        'tax_identification_number' => 'TIN-R',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'rejected',
        'admin_notes' => 'Missing documents.',
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->assertSet('onboardingStatus', 'rejected')
        ->call('reapply')
        ->assertSet('onboardingStatus', 'pending')
        ->assertSet('adminNotes', null)
        ->assertSet('currentStep', 1);

    $this->assertDatabaseHas('shop_onboardings', [
        'id' => $onboarding->id,
        'status' => 'pending',
        'admin_notes' => null,
    ]);
});

test('wizard redirects to the dashboard when onboarding is already approved', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Approved Co',
        'storefront_name' => 'Approved Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-A',
        'tax_identification_number' => 'TIN-A',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'approved',
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->assertRedirect(route('hub.hub-dashboard'));
});

test('submit is a no-op when the application is already submitted', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Submitted Co',
        'storefront_name' => 'Submitted Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-S',
        'tax_identification_number' => 'TIN-S',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 5)
        ->call('submit')
        ->assertHasNoErrors();

    // Status unchanged — the guard prevented resubmission.
    expect($onboarding->fresh()->status)->toBe('submitted');
});

test('goToStep navigates to the given step within bounds', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 4)
        ->call('goToStep', 2)
        ->assertSet('currentStep', 2);
});

test('goToStep ignores out-of-range steps', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 3)
        ->call('goToStep', 0)
        ->assertSet('currentStep', 3)
        ->call('goToStep', 99)
        ->assertSet('currentStep', 3);
});

test('stepForErrorKey maps field keys and prefixes to the correct step', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    $component = Livewire::test(OnboardingWizard::class)->instance();

    expect($component->stepForErrorKey('form.registered_business_name'))->toBe(1)
        ->and($component->stepForErrorKey('form.export_readiness_status'))->toBe(2)
        ->and($component->stepForErrorKey('certificates.0.certification_body'))->toBe(3)
        ->and($component->stepForErrorKey('certificateFiles.1'))->toBe(3)
        ->and($component->stepForErrorKey('documents'))->toBe(5)
        ->and($component->stepForErrorKey('unknown.key'))->toBeNull();
});

test('hasPendingDocuments reflects whether any uploaded document is still scanning', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    $component = Livewire::test(OnboardingWizard::class);

    // No documents yet — nothing pending.
    expect($component->instance()->hasPendingDocuments())->toBeFalse();

    // A pending document makes the checklist "pending".
    $component->set('uploadedDocuments', [
        'business_permit' => ['media_id' => 1, 'filename' => 'permit.pdf', 'scan_status' => 'pending'],
        'dti_sec_certificate' => ['media_id' => 2, 'filename' => 'dti.pdf', 'scan_status' => 'clean'],
    ]);
    expect($component->instance()->hasPendingDocuments())->toBeTrue();

    // Once everything is settled (clean/infected), polling should stop.
    $component->set('uploadedDocuments', [
        'business_permit' => ['media_id' => 1, 'filename' => 'permit.pdf', 'scan_status' => 'clean'],
        'dti_sec_certificate' => ['media_id' => 2, 'filename' => 'dti.pdf', 'scan_status' => 'infected'],
    ]);
    expect($component->instance()->hasPendingDocuments())->toBeFalse();
});

test('the wizard renders its error summary without referencing undefined properties', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    // Trigger step 1 validation errors, then confirm the full component view
    // (including the error summary that calls stepForErrorKey) renders cleanly.
    Livewire::test(OnboardingWizard::class)
        ->set('form.registered_business_name', '')
        ->set('form.storefront_name', '')
        ->call('nextStep')
        ->assertHasErrors('form.registered_business_name')
        ->assertOk()
        ->assertSee(__('Please fix the following before continuing:'));
});

test('a verified document can be replaced when the application requires revision', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Revision Co',
        'storefront_name' => 'Revision Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-R',
        'tax_identification_number' => 'TIN-R',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'requires_revision',
    ]);

    // A document that already passed scanning (the state after a prior submit).
    $media = Media::factory()->create([
        'original_filename' => 'permit.pdf',
        'extension' => 'pdf',
        'scan_status' => 'clean',
        'description' => 'business_permit',
        'uploaded_by_id' => $seller->id,
    ]);

    $onboarding->documents()->attach($media->id);

    $this->actingAs($seller);

    $component = Livewire::test(OnboardingWizard::class)->set('currentStep', 5);

    // The verified document loads and step 5 offers a way to replace it.
    expect($component->get('uploadedDocuments'))->toHaveKey('business_permit');
    $component->assertSee(__('Replace'));

    // Replacing detaches the media and re-opens the slot for a fresh upload.
    $component->call('resetDocument', 'business_permit');

    expect($component->get('uploadedDocuments'))->not->toHaveKey('business_permit');
    $this->assertDatabaseMissing('shop_onboarding_documents', [
        'onboarding_id' => $onboarding->id,
        'media_id' => $media->id,
    ]);
});

test('a rejected application can replace its verified documents after re-applying', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Rejected Co',
        'storefront_name' => 'Rejected Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-RJ',
        'tax_identification_number' => 'TIN-RJ',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'rejected',
        'admin_notes' => 'Documents unclear.',
    ]);

    $media = Media::factory()->create([
        'original_filename' => 'dti.pdf',
        'extension' => 'pdf',
        'scan_status' => 'clean',
        'description' => 'dti_sec_certificate',
        'uploaded_by_id' => $seller->id,
    ]);

    $onboarding->documents()->attach($media->id);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->assertSet('onboardingStatus', 'rejected')
        // Re-apply re-opens the wizard; documents remain attached and replaceable.
        ->call('reapply')
        ->assertSet('onboardingStatus', 'pending')
        ->set('currentStep', 5)
        ->assertSee(__('Replace'))
        ->call('resetDocument', 'dti_sec_certificate')
        ->assertDontSee($media->original_filename);

    $this->assertDatabaseMissing('shop_onboarding_documents', [
        'onboarding_id' => $onboarding->id,
        'media_id' => $media->id,
    ]);
});

test('the revision-required banner prominently shows the reviewer notes', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Banner Co',
        'storefront_name' => 'Banner Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-B',
        'tax_identification_number' => 'TIN-B',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'requires_revision',
        'admin_notes' => 'Please re-upload a clearer DTI certificate.',
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->assertSet('onboardingStatus', 'requires_revision')
        ->assertSee(__('Revision Required'))
        ->assertSee(__('Reviewer notes'))
        ->assertSee('Please re-upload a clearer DTI certificate.');
});
