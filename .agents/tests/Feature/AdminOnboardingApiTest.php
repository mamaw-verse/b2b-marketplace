<?php

declare(strict_types=1);

use App\Events\OnboardingStatusChanged;
use App\Jobs\IndexShopJob;
use App\Models\Media;
use App\Models\ShopOnboarding;
use App\Models\ShopOnboardingCertificate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Queue::fake();
    Event::fake();
});

test('non-admin request to admin onboardings returns 403', function () {
    $user = User::factory()->create();
    $user->assignRole('seller');
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/admin/onboardings');

    $response->assertForbidden();
});

test('admin can list onboardings filtered by status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    // Create onboardings with different statuses
    ShopOnboarding::create([
        'user_id' => User::factory()->create()->id,
        'registered_business_name' => 'Pending Co.',
        'storefront_name' => 'Pending Store',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-2024-00001',
        'tax_identification_number' => '111-222-333-000',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    ShopOnboarding::create([
        'user_id' => User::factory()->create()->id,
        'registered_business_name' => 'Approved Co.',
        'storefront_name' => 'Approved Store',
        'business_structure' => 'sole_proprietorship',
        'dti_sec_number' => 'DTI-2024-00002',
        'tax_identification_number' => '444-555-666-000',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'approved',
        'submitted_at' => now()->subDay(),
    ]);

    $response = $this->getJson('/api/v1/admin/onboardings?status=pending');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.registered_business_name', 'Pending Co.');
});

test('admin can approve a pending onboarding creating shop, certificates, and documents', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $seller = User::factory()->create();

    $media = Media::factory()->create(['uploaded_by_id' => $seller->id]);
    $certMedia = Media::factory()->create(['uploaded_by_id' => $seller->id]);

    $onboarding = ShopOnboarding::create([
        'user_id' => $seller->id,
        'registered_business_name' => 'Halal Corp',
        'storefront_name' => 'Halal Store',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-2024-12345',
        'tax_identification_number' => '123-456-789-000',
        'phone' => '+639171234567',
        'email' => 'halal@corp.ph',
        'address_1' => '123 Halal Street',
        'city' => 'Marawi City',
        'province' => 'Lanao del Sur',
        'zip_code' => '9700',
        'country_code' => 'PH',
        'fda_lto_number' => 'LTO-2024-000001',
        'average_production_lead_time' => 14,
        'export_readiness_status' => 'exporting_regionally',
        'value_chain_positions' => ['processing', 'manufacturing'],
        'capabilities' => ['contract_manufacturing', 'packaging'],
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    // Add a certificate
    ShopOnboardingCertificate::create([
        'onboarding_id' => $onboarding->id,
        'certification_body' => 'IDCP',
        'registration_number' => 'IDCP-2024-00001',
        'expiry_date' => now()->addYear(),
        'media_id' => $certMedia->id,
    ]);

    // Add a document
    $onboarding->documents()->attach($media->id);

    $response = $this->postJson("/api/v1/admin/onboardings/{$onboarding->id}/approve");

    $response->assertOk()
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonStructure(['data' => ['onboarding_id', 'shop_id', 'status']]);

    // Verify shop was created
    $this->assertDatabaseHas('shops', [
        'registered_business_name' => 'Halal Corp',
        'storefront_name' => 'Halal Store',
        'user_id' => $seller->id,
        'status' => 'active',
    ]);

    // Verify certificates were copied
    $this->assertDatabaseHas('shop_certificates', [
        'certification_body' => 'IDCP',
        'registration_number' => 'IDCP-2024-00001',
        'is_verified' => true,
    ]);

    // Verify documents were copied
    $this->assertDatabaseHas('shop_documents', [
        'media_id' => $media->id,
        'is_verified' => true,
    ]);

    // Verify onboarding was updated
    $this->assertDatabaseHas('shop_onboardings', [
        'id' => $onboarding->id,
        'status' => 'approved',
        'reviewed_by_id' => $admin->id,
    ]);

    // Verify IndexShopJob was dispatched
    Queue::assertPushed(IndexShopJob::class);

    // Verify event was dispatched
    Event::assertDispatched(OnboardingStatusChanged::class);
});

test('request-revision without notes returns 422 with VALIDATION_ERROR envelope', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $onboarding = ShopOnboarding::create([
        'user_id' => User::factory()->create()->id,
        'registered_business_name' => 'Test Co.',
        'storefront_name' => 'Test Store',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-2024-99999',
        'tax_identification_number' => '999-888-777-000',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/admin/onboardings/{$onboarding->id}/request-revision", []);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['code', 'message', 'fields']]);
});

test('request-revision with notes updates status and dispatches event', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $onboarding = ShopOnboarding::create([
        'user_id' => User::factory()->create()->id,
        'registered_business_name' => 'Revision Co.',
        'storefront_name' => 'Revision Store',
        'business_structure' => 'partnership',
        'dti_sec_number' => 'DTI-2024-11111',
        'tax_identification_number' => '111-111-111-000',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/admin/onboardings/{$onboarding->id}/request-revision", [
        'notes' => 'Please provide a clearer certificate scan.',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'requires_revision');

    $this->assertDatabaseHas('shop_onboardings', [
        'id' => $onboarding->id,
        'status' => 'requires_revision',
        'admin_notes' => 'Please provide a clearer certificate scan.',
        'reviewed_by_id' => $admin->id,
    ]);

    Event::assertDispatched(OnboardingStatusChanged::class);
});

test('reject without notes returns 422 with VALIDATION_ERROR envelope', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $onboarding = ShopOnboarding::create([
        'user_id' => User::factory()->create()->id,
        'registered_business_name' => 'Reject Co.',
        'storefront_name' => 'Reject Store',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-2024-77777',
        'tax_identification_number' => '777-666-555-000',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/admin/onboardings/{$onboarding->id}/reject", []);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['code', 'message', 'fields']]);
});

test('reject with notes updates status and dispatches event', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $onboarding = ShopOnboarding::create([
        'user_id' => User::factory()->create()->id,
        'registered_business_name' => 'Bad Actor Co.',
        'storefront_name' => 'Bad Store',
        'business_structure' => 'sole_proprietorship',
        'dti_sec_number' => 'DTI-2024-66666',
        'tax_identification_number' => '666-555-444-000',
        'country_code' => 'PH',
        'export_readiness_status' => 'never_exported',
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/admin/onboardings/{$onboarding->id}/reject", [
        'notes' => 'Fraudulent documentation detected.',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    $this->assertDatabaseHas('shop_onboardings', [
        'id' => $onboarding->id,
        'status' => 'rejected',
        'admin_notes' => 'Fraudulent documentation detected.',
        'reviewed_by_id' => $admin->id,
    ]);

    Event::assertDispatched(OnboardingStatusChanged::class);
});
