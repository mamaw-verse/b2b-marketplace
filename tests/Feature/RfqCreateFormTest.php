<?php

declare(strict_types=1);

use App\Livewire\Account\RfqCreateForm;
use App\Models\BuyerProfile;
use App\Models\Product;
use App\Models\Rfq;
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

// =============================================================================
// Step routing
// =============================================================================

test('buyer without a profile lands on step 1', function () {
    $noProfileBuyer = User::factory()->create(['email_verified_at' => now()]);
    $noProfileBuyer->assignRole('buyer');

    Livewire::actingAs($noProfileBuyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id])
        ->assertSet('step', 1)
        ->assertSee('Set up your buyer profile')
        ->assertSee('Continue to RFQ');
});

test('buyer with an existing profile lands directly on step 2', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id])
        ->assertSet('step', 2)
        ->assertDontSee('Set up your buyer profile')
        ->assertSee('Send RFQ');
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

// =============================================================================
// Step 1 — profile save
// =============================================================================

test('saveProfile creates the buyer profile and advances to step 2', function () {
    $noProfileBuyer = User::factory()->create(['email_verified_at' => now()]);
    $noProfileBuyer->assignRole('buyer');

    Livewire::actingAs($noProfileBuyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id])
        ->assertSet('step', 1)
        ->set('business_name', 'Acme Supplies')
        ->set('billing_city', 'Manila')
        ->call('saveProfile')
        ->assertHasNoErrors()
        ->assertSet('step', 2)
        ->assertSee('Send RFQ');

    expect($noProfileBuyer->fresh()->buyerProfile)->not->toBeNull()
        ->and($noProfileBuyer->fresh()->buyerProfile->business_name)->toBe('Acme Supplies');
});

test('saveProfile fails validation when business name is missing', function () {
    $noProfileBuyer = User::factory()->create(['email_verified_at' => now()]);
    $noProfileBuyer->assignRole('buyer');

    Livewire::actingAs($noProfileBuyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id])
        ->set('business_name', '')
        ->call('saveProfile')
        ->assertHasErrors(['business_name' => 'required'])
        ->assertSet('step', 1);
});

test('saveProfile shipping same as billing copies billing fields', function () {
    $noProfileBuyer = User::factory()->create(['email_verified_at' => now()]);
    $noProfileBuyer->assignRole('buyer');

    Livewire::actingAs($noProfileBuyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id])
        ->set('business_name', 'Acme Supplies')
        ->set('billing_city', 'Cebu')
        ->set('useShippingSameAsBilling', true)
        ->call('saveProfile')
        ->assertHasNoErrors();

    $profile = $noProfileBuyer->fresh()->buyerProfile;

    expect($profile->shipping_city)->toBe('Cebu');
});

// =============================================================================
// Step 2 — step indicator & edit link
// =============================================================================

test('step 2 shows an edit profile link pointing to the account profile route', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id])
        ->assertSee(route('account.profile'), false)
        ->assertSee('Edit profile');
});

test('step 1 does not show the edit profile link', function () {
    $noProfileBuyer = User::factory()->create(['email_verified_at' => now()]);
    $noProfileBuyer->assignRole('buyer');

    Livewire::actingAs($noProfileBuyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id])
        ->assertDontSee('Edit profile');
});

// =============================================================================
// Step 2 — RFQ submission
// =============================================================================

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
        ->set('description', 'Some requirements')
        ->set('attachment', UploadedFile::fake()->create('spec.txt', 100, 'text/plain'))
        ->call('submit')
        ->assertHasErrors(['attachment']);
});

test('a PDF attachment passes validation', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->set('description', 'Some requirements')
        ->set('attachment', UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf'))
        ->call('submit')
        ->assertHasNoErrors(['attachment']);
});

test('no attachment is also valid', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->set('description', 'Some requirements')
        ->call('submit')
        ->assertHasNoErrors(['attachment']);
});

test('the page is wrapped in a storefront page container', function () {
    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.create', ['shop_id' => $this->shop->id]))
        ->assertOk()
        ->assertSee('mx-auto max-w-7xl px-4 py-10', false)
        ->assertSee($this->shop->storefront_name);
});

test('without a shop_id the buyer is pointed at the directory instead of a raw shop id field', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class)
        ->assertOk()
        ->assertSee('Choose a supplier first')
        ->assertDontSee('wire:model="shop_id"', false);
});

test('submitting with a published reference product persists it on the RFQ', function () {
    $product = Product::factory()->published()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Halal Frozen Whole Chicken',
    ]);

    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, [
            'shop_id' => $this->shop->id,
            'product_id' => $product->id,
            'title' => 'Quote for chicken',
        ])
        ->set('description', 'Need 500kg bulk order')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Rfq::where('title', 'Quote for chicken')->first()->product_id)->toBe($product->id);
});

test('submitting without description fails validation', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->call('submit')
        ->assertHasErrors(['description' => 'required']);
});

test('submitting without a reference product leaves product_id null', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Generic RFQ'])
        ->set('description', 'General sourcing inquiry')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Rfq::where('title', 'Generic RFQ')->first()->product_id)->toBeNull();
});

test('submitting with a product from another shop does not persist product_id', function () {
    $otherShopProduct = Product::factory()->published()->create([
        'name' => 'Other Shop Product',
    ]);

    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, [
            'shop_id' => $this->shop->id,
            'product_id' => $otherShopProduct->id,
            'title' => 'Cross shop RFQ',
        ])
        ->set('description', 'Cross shop inquiry')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Rfq::where('title', 'Cross shop RFQ')->first()->product_id)->toBeNull();
});

test('submitting with an unpublished product does not persist product_id', function () {
    $archivedProduct = Product::factory()->archived()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Archived Product',
    ]);

    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, [
            'shop_id' => $this->shop->id,
            'product_id' => $archivedProduct->id,
            'title' => 'Archived RFQ',
        ])
        ->set('description', 'Archived product inquiry')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Rfq::where('title', 'Archived RFQ')->first()->product_id)->toBeNull();
});

test('an inactive shop is not treated as a valid recipient', function () {
    $inactiveShop = Shop::factory()->create(['status' => 'suspended']);

    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $inactiveShop->id])
        ->assertOk()
        ->assertSee('Choose a supplier first')
        ->assertDontSee($inactiveShop->storefront_name);
});

test('clearAttachment discards the staged upload', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->set('attachment', UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf'))
        ->assertSee('spec.pdf')
        ->call('clearAttachment')
        ->assertSet('attachment', null)
        ->assertDontSee('spec.pdf');
});

test('the reference product featured image is shown when arriving from a product page', function () {
    $product = Product::factory()->published()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Halal Frozen Whole Chicken',
        'featured_image_path' => 'products/chicken.jpg',
    ]);

    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.create', [
            'shop_id' => $this->shop->id,
            'product_id' => $product->id,
            'title' => $product->name,
        ]))
        ->assertOk()
        ->assertSee('Reference product')
        ->assertSee('Halal Frozen Whole Chicken')
        ->assertSee($product->featured_image_url, false);
});

test('a product belonging to another shop is not shown as the reference', function () {
    $otherShopProduct = Product::factory()->published()->create([
        'name' => 'Not This Products Shop',
        'featured_image_path' => 'products/other.jpg',
    ]);

    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, [
            'shop_id' => $this->shop->id,
            'product_id' => $otherShopProduct->id,
        ])
        ->assertOk()
        ->assertDontSee('Reference product')
        ->assertDontSee('Not This Products Shop');
});

test('an unpublished product is not shown as the reference', function () {
    $archivedProduct = Product::factory()->archived()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Archived Reference Product',
    ]);

    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, [
            'shop_id' => $this->shop->id,
            'product_id' => $archivedProduct->id,
        ])
        ->assertOk()
        ->assertDontSee('Reference product')
        ->assertDontSee('Archived Reference Product');
});

test('a reference product without a featured image falls back to a placeholder', function () {
    $product = Product::factory()->published()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Imageless Product',
        'featured_image_path' => null,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, [
            'shop_id' => $this->shop->id,
            'product_id' => $product->id,
        ])
        ->assertOk()
        ->assertSee('Imageless Product')
        ->assertDontSee('<img', false);
});

test('submitting stays on the page and shows an in-place success message with a link to My RFQs', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->set('description', 'Need bulk pricing')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSee('RFQ submitted')
        ->assertSee('Go to My RFQs')
        ->assertSee(route('account.rfqs.index'), false)
        ->assertDontSee('wire:submit="submit"', false);

    expect(Rfq::where('title', 'Test RFQ')->exists())->toBeTrue();
});

test('the success state cannot be used to submit the RFQ twice', function () {
    $component = Livewire::actingAs($this->buyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->set('description', 'Need bulk pricing')
        ->call('submit')
        ->call('submit');

    $component->assertHasNoErrors();

    expect(Rfq::where('title', 'Test RFQ')->count())->toBe(1);
});

test('submitting the RFQ on step 2 redirects back to step 1 when profile is missing', function () {
    // Simulate an edge case: profile somehow missing when submit fires on step 2.
    $noProfileBuyer = User::factory()->create(['email_verified_at' => now()]);
    $noProfileBuyer->assignRole('buyer');

    Livewire::actingAs($noProfileBuyer)
        ->test(RfqCreateForm::class, ['shop_id' => $this->shop->id, 'title' => 'Test RFQ'])
        ->set('description', 'Need bulk pricing')
        ->set('step', 2) // force step 2 without having a profile
        ->call('submit')
        ->assertSet('step', 1); // falls back to step 1
});
