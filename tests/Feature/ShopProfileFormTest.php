<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\ShopProfileForm;
use App\Models\Category;
use App\Models\Media;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seller = User::factory()->create(['email_verified_at' => now()]);
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create([
        'user_id' => $this->seller->id,
        'status' => 'active',
        'storefront_name' => 'Original Name',
        'city' => 'Manila',
    ]);
});

test('the shop profile form hydrates from the current shop', function () {
    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->assertOk()
        ->assertSet('storefront_name', 'Original Name')
        ->assertSet('slug', 'original-name')
        ->assertSet('city', 'Manila');
});

test('saving updates the shop record', function () {
    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('storefront_name', 'Updated Storefront')
        ->set('city', 'Cebu')
        ->set('export_readiness_status', 'exporting_regionally')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('shops', [
        'id' => $this->shop->id,
        'storefront_name' => 'Updated Storefront',
        'city' => 'Cebu',
        'export_readiness_status' => 'exporting_regionally',
    ]);
});

test('slug can be edited and blank slug regenerates from the storefront name', function () {
    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('slug', 'custom-storefront')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->shop->fresh()->slug)->toBe('custom-storefront');

    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('slug', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->shop->fresh()->slug)->toBe('original-name');
});

test('shop slug validation rejects numeric and reserved values', function () {
    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('slug', '123')
        ->call('save')
        ->assertHasErrors('slug');

    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('slug', 'admin')
        ->call('save')
        ->assertHasErrors('slug');
});

test('validation fails when required fields are cleared', function () {
    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('storefront_name', '')
        ->set('city', '')
        ->call('save')
        ->assertHasErrors([
            'storefront_name' => 'required',
            'city' => 'required',
        ]);
});

test('the form hydrates the previously hidden onboarding fields', function () {
    $this->shop->update([
        'registered_business_name' => 'Acme Foods Inc',
        'business_structure' => 'corporation',
        'value_chain_positions' => ['manufacturing', 'distribution'],
        'capabilities' => ['packaging', 'exporting'],
    ]);

    $category = Category::factory()->create();
    $this->shop->categories()->sync([$category->id]);

    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->assertOk()
        ->assertSet('registered_business_name', 'Acme Foods Inc')
        ->assertSet('business_structure', 'corporation')
        ->assertSet('value_chain_positions', ['manufacturing', 'distribution'])
        ->assertSet('capabilities', ['packaging', 'exporting'])
        ->assertSet('product_categories', [$category->id]);
});

test('saving persists value chain, capabilities, and categories', function () {
    $category = Category::factory()->create();

    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('registered_business_name', 'Renamed Co')
        ->set('business_structure', 'partnership')
        ->set('value_chain_positions', ['farm', 'processing'])
        ->set('capabilities', ['cold_storage'])
        ->set('product_categories', [$category->id])
        ->call('save')
        ->assertHasNoErrors();

    $this->shop->refresh();

    expect($this->shop->registered_business_name)->toBe('Renamed Co')
        ->and($this->shop->business_structure)->toBe('partnership')
        ->and($this->shop->value_chain_positions)->toBe(['farm', 'processing'])
        ->and($this->shop->capabilities)->toBe(['cold_storage'])
        ->and($this->shop->categories->pluck('id')->all())->toBe([$category->id]);
});

test('the read-only verified details show legal identifiers and certificates', function () {
    $this->shop->update([
        'dti_sec_number' => 'SEC-2024-00001',
        'tax_identification_number' => '123-456-789-000',
    ]);

    $media = Media::factory()->create([
        'original_filename' => 'halal-cert.pdf',
        'uploaded_by_id' => $this->seller->id,
    ]);

    ShopCertificate::create([
        'shop_id' => $this->shop->id,
        'certification_body' => 'IDCP',
        'registration_number' => 'REG-001',
        'expiry_date' => now()->addYear(),
        'status' => 'active',
        'media_id' => $media->id,
        'is_verified' => true,
    ]);

    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->assertOk()
        ->assertSee('SEC-2024-00001')
        ->assertSee('123-456-789-000')
        ->assertSee(__('Verified Compliance Details'))
        // Certificate row shows status badge and view-file button; registration_number is not displayed.
        ->assertSee('Active')
        ->assertSee('View file');
});

test('invalid enum values for the new fields are rejected', function () {
    Livewire::actingAs($this->seller)
        ->test(ShopProfileForm::class)
        ->set('business_structure', 'not_a_structure')
        ->set('value_chain_positions', ['bogus_position'])
        ->set('capabilities', ['bogus_capability'])
        ->call('save')
        ->assertHasErrors(['business_structure', 'value_chain_positions.0', 'capabilities.0']);
});
