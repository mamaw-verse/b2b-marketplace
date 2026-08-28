<?php

declare(strict_types=1);

use App\Livewire\Account\Disputes\DisputeForm;
use App\Models\Order;
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

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $this->order = Order::factory()->completed()->create([
        'buyer_user_id' => $this->buyer->id,
        'shop_id' => $this->shop->id,
    ]);
});

test('dispute form shows the advisory message', function () {
    Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->assertSee('recommend chatting with the seller');
});

test('dispute form submits without photos', function () {
    Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->set('type', 'refund_only')
        ->set('reason', 'Item was not as described.')
        ->call('submit')
        ->assertHasNoErrors();
});

test('dispute form accepts image uploads and attaches them to the dispute', function () {
    $component = Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->set('type', 'refund_only')
        ->set('reason', 'Item was damaged.')
        ->set('disputePhotos', [
            UploadedFile::fake()->image('damage1.jpg'),
            UploadedFile::fake()->image('damage2.png'),
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $dispute = $this->order->disputes()->latest()->first();
    expect($dispute)->not->toBeNull();
    expect($dispute->photos()->count())->toBe(2);
});

test('dispute form rejects non-image non-pdf files', function () {
    Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->set('type', 'refund_only')
        ->set('reason', 'Test reason.')
        ->set('disputePhotos', [
            UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
        ])
        ->call('submit')
        ->assertHasErrors(['disputePhotos.*']);
});
