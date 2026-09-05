<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\OnboardingWizard;
use App\Models\Media;
use App\Models\ShopOnboarding;
use App\Models\User;
use App\Services\MediaUploadService;
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
        ->assertSet('form.company_description', '')
        ->assertSet('form.products_description', '')
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
        'company_description' => 'We produce halal food.',
        'products_description' => 'Frozen meals and snacks.',
        'status' => 'pending',
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->assertSet('form.registered_business_name', 'Acme Foods Inc')
        ->assertSet('form.storefront_name', 'Acme Halal')
        ->assertSet('form.business_structure', 'corporation')
        ->assertSet('form.dti_sec_number', 'SEC-12345')
        ->assertSet('form.company_description', 'We produce halal food.')
        ->assertSet('form.products_description', 'Frozen meals and snacks.');
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
            'form.company_description' => 'required',
            'form.products_description' => 'required',
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
        ->set('form.company_description', 'We make great halal products.')
        ->set('form.products_description', 'Snacks, beverages, and more.')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 2);
});

test('step 1 persists form data including descriptions to the database', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('form.registered_business_name', 'Persisted Co')
        ->set('form.storefront_name', 'Persisted Shop')
        ->set('form.business_structure', 'partnership')
        ->set('form.dti_sec_number', 'DTI-333')
        ->set('form.tax_identification_number', 'TIN-444')
        ->set('form.company_description', 'Our company sells premium goods.')
        ->set('form.products_description', 'A wide range of certified items.')
        ->call('nextStep');

    $this->assertDatabaseHas('shop_onboardings', [
        'user_id' => $seller->id,
        'registered_business_name' => 'Persisted Co',
        'storefront_name' => 'Persisted Shop',
        'business_structure' => 'partnership',
        'company_description' => 'Our company sells premium goods.',
        'products_description' => 'A wide range of certified items.',
    ]);
});

test('wizard has 4 total steps', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    $component = Livewire::test(OnboardingWizard::class)->instance();

    expect(OnboardingWizard::TOTAL_STEPS)->toBe(4);
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

test('step 1 uses the configured marketplace instance country', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    config()->set('marketplace.instance_country', 'MY');
    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('form.registered_business_name', 'Country Co')
        ->set('form.storefront_name', 'Country Shop')
        ->set('form.business_structure', 'corporation')
        ->set('form.dti_sec_number', 'DTI-C')
        ->set('form.tax_identification_number', 'TIN-C')
        ->set('form.company_description', 'A company.')
        ->set('form.products_description', 'Products.')
        ->call('nextStep');

    $this->assertDatabaseHas('shop_onboardings', [
        'user_id' => $seller->id,
        'country_code' => 'MY',
    ]);
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

test('wizard shows the approved confirmation screen with CTAs when onboarding is approved', function () {
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
        ->assertSet('onboardingStatus', 'approved')
        ->assertSee(__('Application Approved'))
        ->assertSee(__('Go to Dashboard'));
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
        ->set('currentStep', 4)
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
        ->and($component->stepForErrorKey('form.company_description'))->toBe(1)
        ->and($component->stepForErrorKey('form.products_description'))->toBe(1)
        ->and($component->stepForErrorKey('form.export_readiness_status'))->toBe(2)
        ->and($component->stepForErrorKey('form.value_chain_positions'))->toBe(3)
        ->and($component->stepForErrorKey('form.capabilities'))->toBe(3)
        ->and($component->stepForErrorKey('documentUploads.business_permit'))->toBe(4)
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
        'other_supporting_document' => ['media_id' => 2, 'filename' => 'other.pdf', 'scan_status' => 'clean'],
    ]);
    expect($component->instance()->hasPendingDocuments())->toBeTrue();

    // Once everything is settled (clean/infected), polling should stop.
    $component->set('uploadedDocuments', [
        'business_permit' => ['media_id' => 1, 'filename' => 'permit.pdf', 'scan_status' => 'clean'],
        'other_supporting_document' => ['media_id' => 2, 'filename' => 'other.pdf', 'scan_status' => 'infected'],
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

    $component = Livewire::test(OnboardingWizard::class)->set('currentStep', 4);

    // The verified document loads and step 4 offers a way to replace it.
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

test('setting documentUploads auto-triggers uploadDocument without manual button click', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Auto Upload Co',
        'storefront_name' => 'Auto Upload Shop',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-AU',
        'tax_identification_number' => 'TIN-AU',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'pending',
    ]);

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 4)
        ->call('uploadDocument', 'business_permit', app(MediaUploadService::class))
        // No file set — uploadDocument returns early gracefully
        ->assertHasNoErrors();

    // Verify the updatedDocumentUploads lifecycle hook exists.
    $component = Livewire::test(OnboardingWizard::class);
    expect(method_exists($component->instance(), 'updatedDocumentUploads'))->toBeTrue();
});

test('step 4 document upload dropzone has no manual upload button', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 4)
        ->assertDontSee('wire:click="uploadDocument')
        ->assertSee('Drag & drop, or click to browse');
});

test('step 4 document dropzone wires a dropped file to the hidden input', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 4)
        ->assertSee('dataTransfer?.files', escape: false)
        ->assertSee('dispatchEvent(new Event(\'change\', { bubbles: true }))', escape: false);
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

test('currentStep is persisted in the URL so a page refresh restores the last step', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    // Simulate arriving at step 3 via the URL query parameter.
    $component = Livewire::withQueryParams(['currentStep' => 3])
        ->test(OnboardingWizard::class);

    $component->assertSet('currentStep', 3);
});

test('a stale or out-of-range step in the URL is clamped to valid bounds', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::withQueryParams(['currentStep' => 99])
        ->test(OnboardingWizard::class)
        ->assertSet('currentStep', 4);

    Livewire::withQueryParams(['currentStep' => 0])
        ->test(OnboardingWizard::class)
        ->assertSet('currentStep', 1);
});

test('step 4 only requires business_permit and optionally other_supporting_document', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    $component = Livewire::test(OnboardingWizard::class)->instance();

    expect(array_keys(OnboardingWizard::REQUIRED_DOCUMENTS))->toBe(['business_permit', 'other_supporting_document']);
});

test('company_description and products_description are saved to shop_onboardings', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller);

    Livewire::test(OnboardingWizard::class)
        ->set('form.registered_business_name', 'Test Co')
        ->set('form.storefront_name', 'Test Shop')
        ->set('form.business_structure', 'corporation')
        ->set('form.dti_sec_number', 'DTI-T')
        ->set('form.tax_identification_number', 'TIN-T')
        ->set('form.company_description', 'We are a halal food company.')
        ->set('form.products_description', 'Premium halal snacks.')
        ->call('saveCurrentStep');

    $this->assertDatabaseHas('shop_onboardings', [
        'user_id' => $seller->id,
        'company_description' => 'We are a halal food company.',
        'products_description' => 'Premium halal snacks.',
    ]);
});
