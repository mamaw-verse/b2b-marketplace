<?php

declare(strict_types=1);

use App\Livewire\Account\Disputes\DisputeForm;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    if (! config('marketplace.features.disputes_enabled')) {
        $this->markTestSkipped('disputes_enabled is disabled');
    }

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

    $this->orderItem = OrderItem::factory()->create([
        'order_id' => $this->order->id,
        'quantity' => 10,
        'unit_price' => '100.00',
        'subtotal' => '1000.00',
    ]);

    // Select the order item with a valid quantity — the base state for a valid submit.
    $this->selectItem = fn ($c, int $qty = 3) => $c
        ->set("selectedItems.{$this->orderItem->id}.selected", true)
        ->set("selectedItems.{$this->orderItem->id}.quantity", $qty);
});

test('dispute form shows the advisory message', function () {
    Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->assertSee('recommend chatting with the seller');
});

test('opening the modal resets fields and shows the form', function () {
    Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->assertSet('show', false)
        ->dispatch('open-dispute-modal')
        ->assertSet('show', true)
        ->assertSet('reason', '');
});

test('opening the modal seeds an unselected row per order item', function () {
    Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->dispatch('open-dispute-modal')
        ->assertSet("selectedItems.{$this->orderItem->id}.selected", false)
        ->assertSet("selectedItems.{$this->orderItem->id}.quantity", 10);
});

test('supporting photos are required', function () {
    $c = Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->set('type', 'refund_only')
        ->set('reason', 'Item was not as described.');
    ($this->selectItem)($c);
    $c->call('submit')->assertHasErrors(['disputePhotos' => 'required']);
});

test('at least one item must be selected', function () {
    Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->set('type', 'refund_only')
        ->set('reason', 'Item was not as described.')
        ->set('disputePhotos', [UploadedFile::fake()->image('proof.jpg')])
        ->call('submit')
        ->assertHasErrors(['selectedItems']);
});

test('disputed quantity cannot exceed the ordered quantity', function () {
    $c = Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->set('type', 'refund_only')
        ->set('reason', 'Too many damaged.')
        ->set('disputePhotos', [UploadedFile::fake()->image('proof.jpg')]);
    ($this->selectItem)($c, 11); // ordered was 10
    $c->call('submit')->assertHasErrors(["selectedItems.{$this->orderItem->id}.quantity"]);

    expect($this->order->disputes()->count())->toBe(0);
});

test('dispute submits with selected items, closes the modal, and notifies the parent', function () {
    $c = Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->set('type', 'refund_only')
        ->set('reason', 'Item was not as described.')
        ->set('disputePhotos', [UploadedFile::fake()->image('proof.jpg')]);
    ($this->selectItem)($c, 3);
    $c->call('submit')
        ->assertHasNoErrors()
        ->assertSet('show', false)
        ->assertDispatched('dispute-opened');

    $dispute = $this->order->disputes()->latest()->first();
    expect($dispute)->not->toBeNull()
        ->and($dispute->items()->count())->toBe(1);

    $disputeItem = $dispute->items()->first();
    expect($disputeItem->order_item_id)->toBe($this->orderItem->id)
        ->and((int) $disputeItem->quantity)->toBe(3);
});

test('dispute form accepts image uploads and attaches them to the dispute', function () {
    $c = Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->set('type', 'refund_only')
        ->set('reason', 'Item was damaged.')
        ->set('disputePhotos', [
            UploadedFile::fake()->image('damage1.jpg'),
            UploadedFile::fake()->image('damage2.png'),
        ]);
    ($this->selectItem)($c);
    $c->call('submit')->assertHasNoErrors();

    $dispute = $this->order->disputes()->latest()->first();
    expect($dispute)->not->toBeNull();
    expect($dispute->photos()->count())->toBe(2);
});

test('dispute form rejects non-image non-pdf files', function () {
    $c = Livewire::actingAs($this->buyer)
        ->test(DisputeForm::class, ['order' => $this->order])
        ->set('type', 'refund_only')
        ->set('reason', 'Test reason.')
        ->set('disputePhotos', [
            UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
        ]);
    ($this->selectItem)($c);
    $c->call('submit')->assertHasErrors(['disputePhotos.*']);
});
