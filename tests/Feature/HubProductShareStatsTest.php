<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\ProductForm;
use App\Livewire\CompanyHub\ProductList;
use App\Models\Product;
use App\Models\ProductShareStat;
use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use App\Services\ShopAnalyticsService;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    config()->set('marketplace.features.product_share_enabled', true);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);
});

test('product list displays shares column with share count', function () {
    $product = Product::factory()->published()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Organic Honey',
    ]);
    $product->forceFill(['share_count' => 42])->save();

    Livewire::actingAs($this->seller)
        ->test(ProductList::class)
        ->assertSee('Shares')
        ->assertSee('42');
});

test('product share breakdown groups stats and 30 day window excludes older rows', function () {
    $product = Product::factory()->published()->create(['shop_id' => $this->shop->id]);

    // Older row: 40 days ago
    ProductShareStat::factory()->create([
        'product_id' => $product->id,
        'channel' => 'copy',
        'shared_on' => today()->subDays(40)->toDateString(),
        'count' => 10,
    ]);

    // Recent rows: within 30 days
    ProductShareStat::factory()->create([
        'product_id' => $product->id,
        'channel' => 'copy',
        'shared_on' => today()->subDays(5)->toDateString(),
        'count' => 3,
    ]);

    ProductShareStat::factory()->create([
        'product_id' => $product->id,
        'channel' => 'facebook',
        'shared_on' => today()->subDays(2)->toDateString(),
        'count' => 7,
    ]);

    // Older row for another channel: 45 days ago
    ProductShareStat::factory()->create([
        'product_id' => $product->id,
        'channel' => 'x',
        'shared_on' => today()->subDays(45)->toDateString(),
        'count' => 5,
    ]);

    $analytics = app(ShopAnalyticsService::class);
    $breakdown = $analytics->productShareBreakdown($product, 30);

    // Total all-time: 10 + 3 + 7 + 5 = 25
    expect($breakdown['total'])->toBe(25);
    // Recent (within 30 days): 3 + 7 = 10
    expect($breakdown['last_days'])->toBe(10);

    // Channel stats
    expect($breakdown['by_channel']['copy'])->toBe(['all' => 13, 'recent' => 3]);
    expect($breakdown['by_channel']['facebook'])->toBe(['all' => 7, 'recent' => 7]);
    expect($breakdown['by_channel']['x'])->toBe(['all' => 5, 'recent' => 0]);
    expect($breakdown['by_channel']['native'])->toBe(['all' => 0, 'recent' => 0]);
});

test('product form edit page displays sharing card and breakdown values', function () {
    $product = Product::factory()->published()->create(['shop_id' => $this->shop->id]);

    ProductShareStat::factory()->create([
        'product_id' => $product->id,
        'channel' => 'native',
        'shared_on' => today()->subDays(1)->toDateString(),
        'count' => 8,
    ]);

    Livewire::actingAs($this->seller)
        ->test(ProductForm::class, ['product' => $product])
        ->assertSee('Sharing')
        ->assertSee('Mobile share sheet (incl. Instagram)')
        ->assertSee('8');
});

test('product form edit page hides sharing card when flag is disabled', function () {
    config()->set('marketplace.features.product_share_enabled', false);

    $product = Product::factory()->published()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($this->seller)
        ->test(ProductForm::class, ['product' => $product])
        ->assertDontSee('Mobile share sheet (incl. Instagram)');
});

test('product create page does not display sharing card', function () {
    Livewire::actingAs($this->seller)
        ->test(ProductForm::class)
        ->assertDontSee('Mobile share sheet (incl. Instagram)');
});

test('active shop staff can view sharing stats on product form', function () {
    config()->set('marketplace.features.staff_enabled', true);

    $staff = User::factory()->create(['email_verified_at' => now()]);
    ShopStaff::factory()->active($staff)->create([
        'shop_id' => $this->shop->id,
        'email' => $staff->email,
        'invited_by_id' => $this->seller->id,
    ]);

    $product = Product::factory()->published()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($staff)
        ->test(ProductForm::class, ['product' => $product])
        ->assertOk()
        ->assertSee('Sharing')
        ->assertSee('Mobile share sheet (incl. Instagram)');
});

test('staff or owner of another shop cannot view product form or its stats', function () {
    $otherSeller = User::factory()->create();
    $otherSeller->assignRole('seller');
    $otherShop = Shop::factory()->create(['user_id' => $otherSeller->id, 'status' => 'active']);

    $product = Product::factory()->published()->create(['shop_id' => $this->shop->id]);

    Livewire::actingAs($otherSeller)
        ->test(ProductForm::class, ['product' => $product])
        ->assertForbidden();
});
