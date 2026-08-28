<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\QuotationBuilder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quotation;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $this->rfq = Rfq::factory()->create();
    $this->recipient = RfqRecipient::factory()->inProgress()->create([
        'rfq_id' => $this->rfq->id,
        'shop_id' => $this->shop->id,
    ]);
});

test('quotation builder renders for a new quotation without undefined variable error', function () {
    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        ->assertOk()
        ->assertSee('New Quotation');
});

test('quotation builder renders for an existing quotation', function () {
    $quotation = Quotation::factory()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);

    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, [
            'rfqRecipient' => $this->recipient,
            'quotation' => $quotation,
        ])
        ->assertOk()
        ->assertSee('Edit Quotation');
});

// ── S4.T12 — Remove freeform / null variant option ───────────────────────────

test('the variant picker no longer offers a freeform item option', function () {
    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        ->assertOk()
        ->assertDontSee('Freeform item')
        ->assertSee('Select a product variant');
});

// ── S8.T1 step 3 — Auto-resolve product_id from selected variant ─────────────

test('selectVariant auto-populates the product_id from the chosen variant', function () {
    $product = Product::factory()->create(['shop_id' => $this->shop->id, 'status' => 'published']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit' => 'kg',
        'price' => '120.00',
        'minimum_order_quantity' => 5,
        'status' => 'published',
    ]);

    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        ->call('selectVariant', 0, $variant->id)
        ->assertSet('items.0.variant_id', $variant->id)
        ->assertSet('items.0.product_id', $product->id);
});

test('saveDraft persists the resolved product_id on the quotation item', function () {
    $product = Product::factory()->create(['shop_id' => $this->shop->id, 'status' => 'published']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit' => 'kg',
        'price' => '120.00',
        'minimum_order_quantity' => 5,
        'status' => 'published',
    ]);

    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        ->call('selectVariant', 0, $variant->id)
        ->set('items.0.quantity', '10')
        ->set('items.0.unit_price', '120.00')
        ->call('saveDraft')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('quotation_items', [
        'variant_id' => $variant->id,
        'product_id' => $product->id,
    ]);
});

// ── S2.T5 — Allow zero unit price (free samples) ─────────────────────────────

test('a quotation item accepts a zero unit price for free samples', function () {
    $product = Product::factory()->create(['shop_id' => $this->shop->id, 'status' => 'published']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit' => 'kg',
        'price' => '0.00',
        'minimum_order_quantity' => 1,
        'status' => 'published',
    ]);

    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        ->call('selectVariant', 0, $variant->id)
        ->set('items.0.quantity', '2')
        ->set('items.0.unit_price', '0')
        ->call('saveDraft')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('quotation_items', [
        'variant_id' => $variant->id,
        'unit_price' => '0.00',
    ]);
});
