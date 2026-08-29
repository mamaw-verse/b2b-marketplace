<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\DocumentManager;
use App\Models\Media;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

/**
 * Create a seller with an owned product, acting as that seller.
 */
function sellerProduct(): Product
{
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    test()->actingAs($seller);

    return Product::factory()->for(Shop::factory()->for($seller))->create();
}

function attachDocument(Product $product, string $scanStatus): Media
{
    $media = Media::factory()->create([
        'scan_status' => $scanStatus,
        'uploaded_by_id' => $product->shop->user_id,
    ]);

    $product->documents()->attach($media->id);

    return $media;
}

test('hasPendingDocuments reflects whether any attached document is still scanning', function () {
    $product = sellerProduct();

    $component = Livewire::test(DocumentManager::class, ['product' => $product]);

    // No documents yet — nothing pending, so no polling.
    expect($component->instance()->hasPendingDocuments())->toBeFalse();

    $pending = attachDocument($product, 'pending');
    attachDocument($product, 'clean');

    expect(Livewire::test(DocumentManager::class, ['product' => $product])->instance()->hasPendingDocuments())
        ->toBeTrue();

    // Once every document has settled (clean/infected), polling should stop.
    $pending->update(['scan_status' => 'infected']);

    expect(Livewire::test(DocumentManager::class, ['product' => $product])->instance()->hasPendingDocuments())
        ->toBeFalse();
});

test('the document table polls while a scan is pending and stops once it settles', function () {
    $product = sellerProduct();
    $media = attachDocument($product, 'pending');

    Livewire::test(DocumentManager::class, ['product' => $product])
        ->assertSee('wire:poll.5s', escape: false)
        ->assertSee('Checking scan status automatically');

    $media->update(['scan_status' => 'clean']);

    Livewire::test(DocumentManager::class, ['product' => $product])
        ->assertDontSee('wire:poll.5s', escape: false)
        ->assertDontSee('Checking scan status automatically');
});

test('a poll refresh picks up the scan status written by the scan job', function () {
    $product = sellerProduct();
    $media = attachDocument($product, 'pending');

    $component = Livewire::test(DocumentManager::class, ['product' => $product])
        ->assertSee('Pending');

    // Simulate ScanUploadedFileJob completing between polls.
    $media->update(['scan_status' => 'clean']);

    $component->refresh()
        ->assertSee('Clean')
        ->assertDontSee('Pending');
});
