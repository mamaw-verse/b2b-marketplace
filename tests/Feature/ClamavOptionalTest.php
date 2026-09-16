<?php

declare(strict_types=1);

use App\Jobs\ScanUploadedFileJob;
use App\Livewire\CompanyHub\DocumentManager;
use App\Livewire\CompanyHub\ManualSubscriptionPaymentPage;
use App\Models\Media;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\MediaUploadService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('upload is marked clean with no scan job when scanning is disabled', function () {
    Config::set('services.clamav.enabled', false);
    Storage::fake('s3');
    Queue::fake();

    $user = User::factory()->create();

    $media = app(MediaUploadService::class)->store(
        UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
        $user->id,
        'business_permit'
    );

    expect($media->scan_status)->toBe('clean');
    Queue::assertNotPushed(ScanUploadedFileJob::class);
});

test('upload is marked pending with scan job when scanning is enabled', function () {
    Config::set('services.clamav.enabled', true);
    Storage::fake('s3');
    Queue::fake();

    $user = User::factory()->create();

    $media = app(MediaUploadService::class)->store(
        UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
        $user->id,
        'business_permit'
    );

    expect($media->scan_status)->toBe('pending');
    Queue::assertPushed(ScanUploadedFileJob::class, fn ($job) => $job->media->id === $media->id);
});

test('scan job settles pending media to clean without socket when scanning is disabled', function () {
    Config::set('services.clamav.enabled', false);
    Storage::fake('s3');

    $media = Media::factory()->create(['scan_status' => 'pending']);

    (new ScanUploadedFileJob($media))->handle();

    expect($media->fresh()->scan_status)->toBe('clean');
});

test('pending documents do not block polling gates when scanning is disabled', function () {
    Config::set('services.clamav.enabled', false);
    $this->seed(RolePermissionSeeder::class);

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $product = Product::factory()->for(Shop::factory()->for($seller))->create();
    $media = Media::factory()->create([
        'scan_status' => 'pending',
        'uploaded_by_id' => $seller->id,
    ]);
    $product->documents()->attach($media->id);

    expect(Livewire::actingAs($seller)
        ->test(DocumentManager::class, ['product' => $product])
        ->instance()->hasPendingDocuments())->toBeFalse();
});

test('manual receipt submit accepts a pending receipt when scanning is disabled', function () {
    Config::set('marketplace.payment_method', 'manual');
    Config::set('services.clamav.enabled', false);
    Storage::fake('s3');
    Notification::fake();
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $plan = SubscriptionPlan::factory()->create(['price' => '1200.00', 'currency' => 'PHP', 'trial_days' => null]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'pending_payment',
    ]);

    $media = Media::factory()->create([
        'uploaded_by_id' => $user->id,
        'scan_status' => 'pending',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
    ]);

    Livewire::actingAs($user)
        ->test(ManualSubscriptionPaymentPage::class, ['subscription' => $subscription])
        ->set('referenceNumber', 'REF-CLAMAV-OFF')
        ->set('receiptMediaId', $media->id)
        ->call('submit')
        ->assertSet('step', 'submitted')
        ->assertHasNoErrors();

    expect(PaymentAttempt::where('subscription_id', $subscription->id)->exists())->toBeTrue();
});
