<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\ShopOnboarding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Storage::fake('s3');
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

test('GET /onboarding returns null when no onboarding exists', function () {
    $response = $this->getJson('/api/v1/onboarding');

    $response->assertOk()
        ->assertJson(['data' => null]);
});

test('PUT /onboarding/step/1 creates an onboarding record', function () {
    $response = $this->putJson('/api/v1/onboarding/step/1', [
        'registered_business_name' => 'Maslaha Foods Co.',
        'storefront_name' => 'Maslaha Store',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-2024-001234',
        'tax_identification_number' => '123-456-789-000',
        'phone' => '+639171234567',
        'email' => 'info@maslaha.ph',
        'address_1' => '123 Halal Street',
        'city' => 'Marawi City',
        'province' => 'Lanao del Sur',
        'zip_code' => '9700',
        'country_code' => 'PH',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['onboarding_id', 'step', 'status']]);

    $this->assertDatabaseHas('shop_onboardings', [
        'user_id' => $this->user->id,
        'registered_business_name' => 'Maslaha Foods Co.',
        'storefront_name' => 'Maslaha Store',
    ]);
});

test('PUT /onboarding/step/1 validates business_structure enum', function () {
    $response = $this->putJson('/api/v1/onboarding/step/1', [
        'registered_business_name' => 'Test',
        'storefront_name' => 'Test',
        'business_structure' => 'invalid_value',
        'dti_sec_number' => 'DTI-001',
        'tax_identification_number' => '123-456',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['fields' => ['business_structure']]]);
});

test('PUT /onboarding/step/2 saves operation capacity data', function () {
    // First create step 1
    ShopOnboarding::create([
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

    $response = $this->putJson('/api/v1/onboarding/step/2', [
        'product_categories' => 'Bakery,Confectionery',
        'fda_lto_number' => 'LTO-2024-123456',
        'average_production_lead_time' => 14,
        'export_readiness_status' => 'exporting_regionally',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('shop_onboardings', [
        'user_id' => $this->user->id,
        'product_categories' => 'Bakery,Confectionery',
    ]);
});

test('PUT /onboarding/step/4 validates value chain positions', function () {
    ShopOnboarding::create([
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

    $response = $this->putJson('/api/v1/onboarding/step/4', [
        'value_chain_positions' => ['processing', 'manufacturing'],
        'capabilities' => ['packaging', 'cold_storage'],
    ]);

    $response->assertOk();

    $onboarding = ShopOnboarding::where('user_id', $this->user->id)->first();
    expect($onboarding->value_chain_positions)->toBe(['processing', 'manufacturing']);
    expect($onboarding->capabilities)->toBe(['packaging', 'cold_storage']);
});

test('PUT /onboarding/step/4 rejects invalid value chain positions', function () {
    ShopOnboarding::create([
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

    $response = $this->putJson('/api/v1/onboarding/step/4', [
        'value_chain_positions' => ['invalid_position'],
        'capabilities' => ['packaging'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['fields' => ['value_chain_positions.0']]]);
});

test('POST /onboarding/certificates adds a certificate', function () {
    $onboarding = ShopOnboarding::create([
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

    $media = Media::factory()->create(['uploaded_by_id' => $this->user->id]);

    $response = $this->postJson('/api/v1/onboarding/certificates', [
        'certification_body' => 'IDCP',
        'registration_number' => 'IDCP-2026-00931',
        'expiry_date' => now()->addYear()->toDateString(),
        'media_id' => $media->id,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'certification_body', 'expiry_date']]);

    expect($onboarding->certificates()->count())->toBe(1);
});

test('POST /onboarding/submit fails when incomplete', function () {
    ShopOnboarding::create([
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

    $response = $this->postJson('/api/v1/onboarding/submit');

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['code', 'message', 'fields']]);
});

test('POST /onboarding/submit succeeds when complete', function () {
    $onboarding = ShopOnboarding::create([
        'user_id' => $this->user->id,
        'registered_business_name' => 'Complete Co',
        'storefront_name' => 'Complete Store',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-001',
        'tax_identification_number' => '123-456-789',
        'country_code' => 'PH',
        'product_categories' => 'Bakery,Food',
        'export_readiness_status' => 'never_exported',
        'value_chain_positions' => ['processing'],
        'capabilities' => ['packaging'],
    ]);

    // Add certificate
    $media = Media::factory()->create([
        'uploaded_by_id' => $this->user->id,
        'scan_status' => 'clean',
    ]);
    $onboarding->certificates()->create([
        'certification_body' => 'IDCP',
        'registration_number' => 'IDCP-001',
        'expiry_date' => now()->addYear(),
        'media_id' => $media->id,
    ]);

    // Add document (clean)
    $docMedia = Media::factory()->create([
        'uploaded_by_id' => $this->user->id,
        'scan_status' => 'clean',
    ]);
    $onboarding->documents()->attach($docMedia->id);

    $response = $this->postJson('/api/v1/onboarding/submit');

    $response->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonStructure(['data' => ['onboarding_id', 'status', 'submitted_at']]);

    expect($onboarding->fresh()->status)->toBe('pending');
    expect($onboarding->fresh()->submitted_at)->not->toBeNull();
});

test('unauthenticated request to onboarding returns 401', function () {
    Sanctum::actingAs(User::factory()->create()); // Reset
    // Make an unauthenticated request
    $response = $this->withHeaders(['Authorization' => ''])->getJson('/api/v1/onboarding');

    // Sanctum is still acting — let's test properly
    $this->app['auth']->forgetGuards();
    $response = $this->getJson('/api/v1/onboarding');

    $response->assertUnauthorized();
});
