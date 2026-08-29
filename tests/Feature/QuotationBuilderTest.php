<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\QuotationBuilder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quotation;
use App\Models\QuotationItem;
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

// ── Custom unit via "Other…" ─────────────────────────────────────────────────

test('the unit field offers an Other option for a custom unit', function () {
    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        ->assertOk()
        ->assertSee('Other…');
});

test('saveDraft persists a custom typed unit value', function () {
    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        ->set('items.0.description', 'Bespoke bulk order')
        ->set('items.0.quantity', '3')
        ->set('items.0.unit', 'pallet-crate') // typed custom unit, not in config('units')
        ->set('items.0.unit_price', '250.00')
        ->call('saveDraft')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('quotation_items', [
        'unit' => 'pallet-crate',
        'description' => 'Bespoke bulk order',
    ]);
});

// ── Description prefill on first load (edit mode) ─────────────────────────────

test('the description is available in state on first load when editing', function () {
    $quotation = Quotation::factory()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);
    QuotationItem::factory()->create([
        'quotation_id' => $quotation->id,
        'description' => 'Halal Beef Cubes 10kg',
        'unit' => 'kg',
    ]);

    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, [
            'rfqRecipient' => $this->recipient,
            'quotation' => $quotation,
        ])
        ->assertOk()
        // The field is Alpine-hydrated from this state on init, so assert the
        // state carries the value the input renders from.
        ->assertSet('items.0.description', 'Halal Beef Cubes 10kg')
        ->assertSet('items.0.unit', 'kg');
});

// ── selectVariant prefills description + unit (issues 2 & 3) ──────────────────

test('selectVariant prefills the description and unit from the chosen variant', function () {
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'status' => 'published',
        'name' => 'Halal Coconut Flour',
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'description' => '25kg sack',
        'unit' => 'sack',
        'price' => '480.00',
        'minimum_order_quantity' => 4,
        'status' => 'published',
    ]);

    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        ->call('selectVariant', 0, $variant->id)
        ->assertSet('items.0.description', 'Halal Coconut Flour — 25kg sack')
        ->assertSet('items.0.unit', 'sack')
        ->assertSet('items.0.quantity', '4')
        ->assertSet('items.0.unit_price', '480.00');
});

// ── Error flags for wire:ignore fields (red ring) ────────────────────────────

test('validation failure sets itemFieldErrors so ignored fields can show the red ring', function () {
    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        // Leave the first item entirely blank, then attempt to save.
        ->set('items.0.description', '')
        ->set('items.0.unit', '')
        ->call('saveDraft')
        ->assertHasErrors(['items.0.description', 'items.0.unit'])
        ->assertSet('itemFieldErrors.0.description', true)
        ->assertSet('itemFieldErrors.0.unit', true);
});

test('itemFieldErrors clears for fields that become valid on the next validation', function () {
    Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        ->set('items.0.description', '')
        ->set('items.0.unit', '')
        ->call('saveDraft')
        ->assertSet('itemFieldErrors.0.description', true)
        // Fill description + unit (still missing qty/price), revalidate.
        ->set('items.0.description', 'Halal Chicken 5kg')
        ->set('items.0.unit', 'kg')
        ->call('saveDraft')
        ->assertSet('itemFieldErrors.0.description', false)
        ->assertSet('itemFieldErrors.0.unit', false);
});

// ── Selecting a variant clears stale errors (issue 1) ────────────────────────

test('selecting a variant clears validation errors and itemFieldErrors for that row', function () {
    $product = Product::factory()->create(['shop_id' => $this->shop->id, 'status' => 'published']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit' => 'kg',
        'price' => '120.00',
        'minimum_order_quantity' => 5,
        'status' => 'published',
    ]);

    $component = Livewire::actingAs($this->seller)
        ->test(QuotationBuilder::class, ['rfqRecipient' => $this->recipient])
        // Trigger validation with a blank row so errors are set.
        ->call('saveDraft')
        ->assertHasErrors(['items.0.description', 'items.0.quantity', 'items.0.unit', 'items.0.unit_price'])
        ->assertSet('itemFieldErrors.0.description', true)
        ->assertSet('itemFieldErrors.0.unit', true);

    // Selecting a variant should wipe the stale errors for this row.
    $component->call('selectVariant', 0, $variant->id)
        ->assertHasNoErrors([
            'items.0.description',
            'items.0.quantity',
            'items.0.unit',
            'items.0.unit_price',
        ])
        ->assertSet('itemFieldErrors.0.description', false)
        ->assertSet('itemFieldErrors.0.unit', false);
});
