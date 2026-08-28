<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\VariantManager;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id]);
    $this->product = Product::factory()->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->seller);
});

test('opening add variant resets the form to blank state', function () {
    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('openAddVariant')
        ->assertSet('showVariantModal', true)
        ->assertSet('form.editingVariantId', null)
        ->assertSet('form.sku', '')
        ->assertSet('form.moq', '1')
        ->assertSet('form.status', 'archived');
});

test('saving a new variant persists it via the form object', function () {
    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('openAddVariant')
        ->set('form.sku', 'SKU-NEW-001')
        ->set('form.price', '150.00')
        ->set('form.moq', '10')
        ->set('form.unit', 'kg')
        ->set('form.status', 'published')
        ->call('saveVariant')
        ->assertHasNoErrors()
        ->assertSet('showVariantModal', false);

    $this->assertDatabaseHas('product_variants', [
        'product_id' => $this->product->id,
        'sku' => 'SKU-NEW-001',
        'minimum_order_quantity' => 10,
        'unit' => 'kg',
        'status' => 'published',
    ]);
});

test('variant form validation fails when required fields are empty', function () {
    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('openAddVariant')
        ->set('form.sku', '')
        ->set('form.price', '')
        ->set('form.unit', '')
        ->call('saveVariant')
        ->assertHasErrors([
            'form.sku' => 'required',
            'form.price' => 'required',
            'form.unit' => 'required',
        ]);
});

test('opening edit variant hydrates the form object', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'SKU-EDIT-1',
        'price' => '99.50',
        'minimum_order_quantity' => 5,
        'unit' => 'box',
        'status' => 'published',
    ]);

    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('openEditVariant', $variant->id)
        ->assertSet('form.editingVariantId', $variant->id)
        ->assertSet('form.sku', 'SKU-EDIT-1')
        ->assertSet('form.moq', '5')
        ->assertSet('form.unit', 'box')
        ->assertSet('form.status', 'published');
});

test('unit select is wired to hydrate from the live form value on edit', function () {
    // Regression: the hs-unit-select component must sync its Alpine state FROM
    // the Livewire value so the unit shows when a variant is edited.
    $variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'unit' => 'box',
    ]);

    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('openEditVariant', $variant->id)
        ->assertSet('form.unit', 'box')
        // The unit select must watch the live wire value and hydrate from it.
        ->assertSeeHtml("\$watch('\$wire.form.unit'")
        ->assertSeeHtml("hydrateFrom(\$wire.get('form.unit'))");
});

test('editing a variant updates it via the form object', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'SKU-OLD',
        'unit' => 'pcs',
    ]);

    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('openEditVariant', $variant->id)
        ->set('form.sku', 'SKU-UPDATED')
        ->set('form.price', '200.00')
        ->set('form.moq', '20')
        ->set('form.unit', 'crate')
        ->call('saveVariant')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'sku' => 'SKU-UPDATED',
        'minimum_order_quantity' => 20,
        'unit' => 'crate',
    ]);
});

// ── S4.T7 — Variant deletion removed ─────────────────────────────────────────

test('variant deletion methods are no longer callable', function () {
    expect(method_exists(VariantManager::class, 'deleteVariant'))->toBeFalse();
    expect(method_exists(VariantManager::class, 'removeVariant'))->toBeFalse();
    expect(method_exists(VariantManager::class, 'confirmRemoveVariant'))->toBeFalse();
});

test('the variant list does not render a delete confirmation modal', function () {
    ProductVariant::factory()->create(['product_id' => $this->product->id]);

    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->assertOk()
        ->assertDontSeeHtml('confirm-remove-variant')
        ->assertDontSee('confirmRemoveVariant');
});
