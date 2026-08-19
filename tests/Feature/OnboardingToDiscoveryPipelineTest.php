<?php

declare(strict_types=1);

use App\Jobs\IndexProductJob;
use App\Jobs\IndexShopJob;
use App\Jobs\ScanUploadedFileJob;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('full pipeline: onboarding → approval → product → discovery', function () {
    // Seed roles/permissions
    $this->seed(RolePermissionSeeder::class);

    Queue::fake();
    Storage::fake('s3');

    // ─── Step 1: Create user with verified email ───────────────────────
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('seller');
    Sanctum::actingAs($user);

    // ─── Step 2: Complete all 5 onboarding steps via API ───────────────
    // Step 1: Company profile
    $this->putJson('/api/v1/onboarding/step/1', [
        'registered_business_name' => 'Halal Goods Philippines Inc.',
        'storefront_name' => 'Halal Goods PH',
        'business_structure' => 'corporation',
        'dti_sec_number' => 'SEC-2026-999001',
        'tax_identification_number' => '999-888-777-000',
        'phone' => '+639171234567',
        'email' => 'info@halalgoods.ph',
        'address_1' => '123 Halal Boulevard',
        'city' => 'Marawi City',
        'province' => 'Lanao del Sur',
        'zip_code' => '9700',
        'country_code' => 'PH',
    ])->assertOk();

    // Step 2: Operation capacity
    $this->putJson('/api/v1/onboarding/step/2', [
        'product_categories' => 'Bakery,Confectionery,Beverages',
        'fda_lto_number' => 'LTO-2026-123456',
        'average_production_lead_time' => 14,
        'export_readiness_status' => 'exporting_regionally',
    ])->assertOk();

    // Step 3: (Certificates handled separately, no-op step data if needed)
    $this->putJson('/api/v1/onboarding/step/3', [])->assertOk();

    // Step 4: Value chain positions
    $this->putJson('/api/v1/onboarding/step/4', [
        'value_chain_positions' => ['processing', 'manufacturing'],
        'capabilities' => ['packaging', 'cold_storage', 'exporting'],
    ])->assertOk();

    // Step 5: (Documents handled separately, no-op step data if needed)
    $this->putJson('/api/v1/onboarding/step/5', [])->assertOk();

    // ─── Step 3: Add certificate + documents ───────────────────────────
    $certMedia = Media::factory()->create([
        'uploaded_by_id' => $user->id,
        'scan_status' => 'clean',
    ]);

    $this->postJson('/api/v1/onboarding/certificates', [
        'certification_body' => 'IDCP',
        'registration_number' => 'IDCP-2026-99999',
        'expiry_date' => now()->addYear()->toDateString(),
        'media_id' => $certMedia->id,
    ])->assertCreated();

    // Upload document
    $docFile = UploadedFile::fake()->create('business_permit.pdf', 1024, 'application/pdf');
    $this->postJson('/api/v1/onboarding/documents', [
        'file' => $docFile,
        'description' => 'Business permit',
    ])->assertCreated();

    Queue::assertPushed(ScanUploadedFileJob::class);

    // Mark all uploaded documents as clean (simulating antivirus scan)
    Media::where('scan_status', 'pending')->update(['scan_status' => 'clean']);

    // ─── Step 4: Submit onboarding ─────────────────────────────────────
    $this->postJson('/api/v1/onboarding/submit')
        ->assertOk()
        ->assertJsonPath('data.status', 'pending');

    // ─── Step 5: Admin approves onboarding ─────────────────────────────
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sanctum::actingAs($admin);

    $onboarding = $user->shopOnboarding;
    $this->postJson("/api/v1/admin/onboardings/{$onboarding->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    Queue::assertPushed(IndexShopJob::class);

    // ─── Step 6: Verify shop is created ────────────────────────────────
    $user->refresh();
    expect($user->shop)->not->toBeNull();
    expect($user->shop->status)->toBe('active');
    expect($user->shop->registered_business_name)->toBe('Halal Goods Philippines Inc.');

    // ─── Step 7: Create product + variant ──────────────────────────────
    Sanctum::actingAs($user);

    $productResponse = $this->postJson('/api/v1/hub/products', [
        'name' => 'Premium Halal Coconut Oil',
        'description' => 'Cold-pressed virgin coconut oil, halal certified.',
    ])->assertCreated();

    $productId = $productResponse->json('data.id');

    $this->postJson("/api/v1/hub/products/{$productId}/variants", [
        'sku' => 'HCO-500ML',
        'price' => 450.00,
        'currency' => 'PHP',
        'minimum_order_quantity' => 50,
        'unit' => 'bottle',
        'available_quantity' => 10000,
        'lead_time_days' => 7,
        'status' => 'published',
    ])->assertCreated();

    // ─── Step 8: Publish product ───────────────────────────────────────
    $this->postJson("/api/v1/hub/products/{$productId}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    Queue::assertPushed(IndexProductJob::class);

    // ─── Step 9: Verify product appears via public API ─────────────────
    $response = $this->getJson("/api/v1/products/{$productId}");

    $response->assertOk()
        ->assertJsonPath('data.id', $productId)
        ->assertJsonPath('data.name', 'Premium Halal Coconut Oil')
        ->assertJsonPath('data.status', 'published');

    // Verify variants are included
    $response->assertJsonCount(1, 'data.variants');
    expect($response->json('data.variants.0.sku'))->toBe('HCO-500ML');
});
