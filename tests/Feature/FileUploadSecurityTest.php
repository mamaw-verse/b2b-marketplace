<?php

declare(strict_types=1);

use App\Models\ShopOnboarding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Storage::fake('s3');

    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);

    // Create onboarding so the documents endpoint works
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
});

test('oversized file (>20MB) is rejected with 422', function () {
    // Create a file that's 21MB
    $file = UploadedFile::fake()->create('large_document.pdf', 21 * 1024, 'application/pdf');

    $response = $this->postJson('/api/v1/onboarding/documents', [
        'file' => $file,
        'description' => 'Too large',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['fields' => ['file']]]);
});

test('disallowed MIME type (application/x-msdownload .exe) is rejected', function () {
    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    $response = $this->postJson('/api/v1/onboarding/documents', [
        'file' => $file,
        'description' => 'Executable file',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['fields' => ['file']]]);
});

test('disallowed extension (.bat) is rejected', function () {
    $file = UploadedFile::fake()->create('script.bat', 50, 'text/plain');

    $response = $this->postJson('/api/v1/onboarding/documents', [
        'file' => $file,
        'description' => 'Batch file',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['fields' => ['file']]]);
});

test('disallowed extension (.sh) is rejected', function () {
    $file = UploadedFile::fake()->create('deploy.sh', 50, 'application/x-sh');

    $response = $this->postJson('/api/v1/onboarding/documents', [
        'file' => $file,
        'description' => 'Shell script',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['fields' => ['file']]]);
});

test('valid PDF file is accepted', function () {
    $file = UploadedFile::fake()->create('business_permit.pdf', 1024, 'application/pdf');

    $response = $this->postJson('/api/v1/onboarding/documents', [
        'file' => $file,
        'description' => 'Business permit',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['media_id', 'original_filename', 'storage_path']]);
});

test('valid JPG image is accepted', function () {
    $file = UploadedFile::fake()->image('certificate_scan.jpg', 800, 600);

    $response = $this->postJson('/api/v1/onboarding/documents', [
        'file' => $file,
        'description' => 'Certificate scan',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['media_id', 'original_filename', 'storage_path']]);
});

test('valid PNG image is accepted', function () {
    $file = UploadedFile::fake()->image('logo.png', 200, 200);

    $response = $this->postJson('/api/v1/onboarding/documents', [
        'file' => $file,
        'description' => 'Company logo',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['media_id', 'original_filename', 'storage_path']]);
});

test('file at exactly 20MB boundary is accepted', function () {
    // 20MB = 20480 KB (the max)
    $file = UploadedFile::fake()->create('max_size.pdf', 20480, 'application/pdf');

    $response = $this->postJson('/api/v1/onboarding/documents', [
        'file' => $file,
        'description' => 'Maximum size file',
    ]);

    $response->assertCreated();
});

test('request without file is rejected', function () {
    $response = $this->postJson('/api/v1/onboarding/documents', [
        'description' => 'No file attached',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['error' => ['fields' => ['file']]]);
});
