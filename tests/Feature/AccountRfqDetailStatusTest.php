<?php

declare(strict_types=1);

use App\Livewire\Account\RfqDetail;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->buyer = User::factory()->create(['email_verified_at' => now()]);
    $this->buyer->assignRole('buyer');

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $this->rfq = Rfq::factory()->create(['user_id' => $this->buyer->id]);
    $this->recipient = RfqRecipient::factory()->create([
        'rfq_id' => $this->rfq->id,
        'shop_id' => $this->shop->id,
        'status' => 'in_progress',
        'admin_status' => 'pending',
    ]);
});

test('buyer RFQ detail reflects the admin status via the status pill', function (string $adminStatus, string $label) {
    $this->recipient->update(['admin_status' => $adminStatus]);

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertSee($label);
})->with([
    'pending' => ['pending', 'Pending'],
    'in_progress' => ['in_progress', 'In Progress'],
    'resolved' => ['resolved', 'Resolved'],
    'closed' => ['closed', 'Closed'],
]);

test('buyer RFQ detail treats a missing admin status as pending', function () {
    $this->recipient->update(['admin_status' => null]);

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertSee('Pending');
});

test('buyer RFQ detail does not render the quotation list', function () {
    Quotation::factory()->submitted()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);

    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.show', $this->rfq))
        ->assertOk()
        ->assertDontSee('Quotations')
        ->assertDontSee('No quotations received yet.');
});

test('buyer RFQ detail does not expose award or reject actions', function () {
    Quotation::factory()->submitted()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);

    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.show', $this->rfq))
        ->assertOk()
        ->assertDontSee('Award')
        ->assertDontSee('Reject');
});

test('buyer RFQ detail shows the product the quotation is being requested for', function () {
    $product = Product::factory()->published()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Halal Frozen Whole Chicken',
        'featured_image_path' => 'products/chicken.jpg',
    ]);

    $this->rfq->update(['product_id' => $product->id]);

    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.show', $this->rfq))
        ->assertOk()
        ->assertSee('Product for quotation')
        ->assertSee('Halal Frozen Whole Chicken')
        ->assertSee($product->featured_image_url, false)
        ->assertSee(route('storefront.product', $product), false);
});

test('buyer RFQ detail omits the product block when no product is linked', function () {
    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.show', $this->rfq))
        ->assertOk()
        ->assertDontSee('Product for quotation');
});
