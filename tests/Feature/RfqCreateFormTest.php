<?php

declare(strict_types=1);

use App\Livewire\Account\RfqCreateForm;
use App\Models\BuyerProfile;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('s3');
    Storage::fake('local');

    $this->buyer = User::factory()->create(['email_verified_at' => now()]);
    $this->buyer->assignRole('buyer');

    $this->shop = Shop::factory()->create(['status' => 'active']);

    // Buyer profile needed for submit
    BuyerProfile::factory()->create(['user_id' => $this->buyer->id]);
});

test('the RFQ form renders without redirecting even when buyer profile is absent', function () {
    $noProfileBuyer = User::factory()->create(['email_verified_at' => now()]);
    $noProfileBuyer->assignRole('buyer');

    Livewire::actingAs($noProfileBuyer)
        ->test(RfqCreateForm::class)
        ->assertOk()
        ->assertNoRedirect();
});

test('the RFQ form renders in the storefront layout', function () {
    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.create', ['shop_id' => $this->shop->id]))
        ->assertOk()
        ->assertSee('Request for Quotation');
});

test('attachment uses a single file input (not multiple)', function () {
    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.create', ['shop_id' => $this->shop->id]))
        ->assertOk()
        ->assertDontSee('multiple', false)
        ->assertSee('wire:model="attachment"', false);
});

test('text/plain file is rejected with the correct error message', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->set('attachment', UploadedFile::fake()->create('spec.txt', 100, 'text/plain'))
        ->call('submit')
        ->assertHasErrors(['attachment']);
});

test('a PDF attachment passes validation', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->set('attachment', UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf'))
        ->call('submit')
        ->assertHasNoErrors(['attachment']);
});

test('no attachment is also valid', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->call('submit')
        ->assertHasNoErrors(['attachment']);
});
