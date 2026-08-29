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

// ── Unit field alignment in the add/edit variant modal ───────────────────────

test('the unit field matches flux field metrics so it lines up with price and moq', function () {
    // Regression: hs-unit-select renders its own label/control instead of using
    // flux:input, so its metrics must mirror flux's — an inline-flex label with
    // leading-tight and an 8px gap. A plain block label with the default
    // line-height pushes the select ~3px below its flux siblings in the row.
    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('openAddVariant')
        // The unit cell uses the same grid gap-2 field layout as a flux field
        // (label + control) so its box top lines up with Price and MOQ.
        ->assertSeeHtml('class="grid gap-2"')
        ->assertSeeHtml('inline-flex items-center text-sm font-medium leading-tight')
        // The select must suppress the browser's square focus outline so the
        // rounded green ring matches the input radius.
        ->assertSeeHtml('appearance-none')
        ->assertSeeHtml('focus:outline-none');
});

test('the add/edit variant modal cannot be dismissed by clicking outside', function () {
    // Prevents losing in-progress variant edits from a stray background click.
    // Flux maps :dismissible="false" to the disable-click-outside attribute.
    // The Quantity Breaks modal stays dismissible (default).
    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('openAddVariant')
        ->assertSeeHtml('disable-click-outside');
});

test('the unit field renders as sibling grid cells that expand to 4 columns only for custom units', function () {
    // layout="grid" makes the root display:contents so the select and the
    // custom-unit input become sibling grid cells. The row is 3 columns by
    // default and only becomes 4 when the chosen unit is custom ("Other"), so
    // Min. Order Qty never shifts down for standard units.
    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('openAddVariant')
        ->assertSeeHtml('class="contents"')
        ->assertSeeHtml("isCustomUnit ? 'grid-cols-4' : 'grid-cols-3'");
});

// ── Status toggle is an explicit action button, not the badge ────────────────

test('toggleStatus flips a variant between published and archived', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'status' => 'published',
    ]);

    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->call('toggleStatus', $variant->id);

    expect($variant->fresh()->status)->toBe('archived');
});

test('the status pill is display-only and toggling is a tooltip icon button', function () {
    // The pill used to be wrapped in a <button wire:click="toggleStatus"> which
    // was confusing. Status changes now go through a dedicated icon button with
    // a Publish/Archive tooltip, matching the product-list actions.
    $variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'status' => 'published',
    ]);

    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->assertOk()
        // Published variant offers an Archive action.
        ->assertSeeHtml('tooltip="Archive"')
        ->assertSeeHtml('wire:click="toggleStatus('.$variant->id.')"')
        // The pill itself no longer wraps the toggle in a clickable button.
        ->assertDontSeeHtml('<button wire:click="toggleStatus');
});

test('an archived variant offers a Publish toggle', function () {
    ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'status' => 'archived',
    ]);

    Livewire::test(VariantManager::class, ['product' => $this->product])
        ->assertSeeHtml('tooltip="Publish"');
});
