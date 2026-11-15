<?php

declare(strict_types=1);

use App\Livewire\Storefront\HeroSearch;
use App\Livewire\Storefront\ProductDirectory;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCertificate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Scout\EngineManager;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Config::set('scout.driver', 'pgsql');
    Config::set('marketplace.features.subscriptions_enabled', false);
    app(EngineManager::class)->forgetEngines();
});

test('home page featured products only show halal badge for shops with active certificate', function () {
    $certifiedShop = Shop::factory()->create(['status' => 'active', 'storefront_name' => 'Certified Organic Shop']);
    ShopCertificate::factory()->create([
        'shop_id' => $certifiedShop->id,
        'status' => 'active',
    ]);
    $certifiedProduct = Product::factory()->published()->create([
        'shop_id' => $certifiedShop->id,
        'name' => 'Certified Halal Beef',
    ]);

    $uncertifiedShop = Shop::factory()->create(['status' => 'active', 'storefront_name' => 'Uncertified Farm Shop']);
    $uncertifiedProduct = Product::factory()->published()->create([
        'shop_id' => $uncertifiedShop->id,
        'name' => 'Uncertified Organic Rice',
    ]);

    $expiredShop = Shop::factory()->create(['status' => 'active', 'storefront_name' => 'Expired Cert Shop']);
    ShopCertificate::factory()->expired()->create([
        'shop_id' => $expiredShop->id,
    ]);
    $expiredProduct = Product::factory()->published()->create([
        'shop_id' => $expiredShop->id,
        'name' => 'Expired Cert Tea',
    ]);

    $component = Livewire::test(HeroSearch::class);

    $html = $component->html();

    // Certified product card renders the top-2 left-2 halal badge container
    expect($html)->toContain($certifiedProduct->name);
    expect($html)->toContain($uncertifiedProduct->name);
    expect($html)->toContain($expiredProduct->name);

    // Render individual product cards to assert exact badge gating
    $certifiedCard = (string) $this->blade('<x-hs-product-card :product="$product" />', ['product' => $certifiedProduct]);
    expect($certifiedCard)->toContain('absolute top-2 left-2');
    expect($certifiedCard)->toContain('Halal');

    $uncertifiedCard = (string) $this->blade('<x-hs-product-card :product="$product" />', ['product' => $uncertifiedProduct]);
    expect($uncertifiedCard)->not->toContain('absolute top-2 left-2');
    expect($uncertifiedCard)->not->toContain('Halal');

    $expiredCard = (string) $this->blade('<x-hs-product-card :product="$product" />', ['product' => $expiredProduct]);
    expect($expiredCard)->not->toContain('absolute top-2 left-2');
    expect($expiredCard)->not->toContain('Halal');
});

test('home page search results only show halal badge for shops with active certificate', function () {
    $certifiedShop = Shop::factory()->create(['status' => 'active', 'storefront_name' => 'Al-Barakah Spices']);
    ShopCertificate::factory()->create([
        'shop_id' => $certifiedShop->id,
        'status' => 'active',
    ]);
    Product::factory()->published()->create([
        'shop_id' => $certifiedShop->id,
        'name' => 'Saffron Powder Special',
    ]);

    $uncertifiedShop = Shop::factory()->create(['status' => 'active', 'storefront_name' => 'Common Spice Mill']);
    Product::factory()->published()->create([
        'shop_id' => $uncertifiedShop->id,
        'name' => 'Saffron Threads Standard',
    ]);

    $component = Livewire::test(HeroSearch::class)
        ->set('query', 'Saffron')
        ->call('search');

    $results = $component->get('productResults');
    expect($results)->toHaveCount(2);

    $results->each(function (Product $product) {
        expect($product->relationLoaded('shop'))->toBeTrue();
        expect($product->shop->relationLoaded('certificates'))->toBeTrue();
    });
});

test('product catalog directory only shows halal badge for shops with active certificate', function () {
    $certifiedShop = Shop::factory()->create(['status' => 'active']);
    ShopCertificate::factory()->create([
        'shop_id' => $certifiedShop->id,
        'status' => 'active',
    ]);
    $certifiedProduct = Product::factory()->published()->create([
        'shop_id' => $certifiedShop->id,
        'name' => 'Certified Virgin Coconut Oil',
    ]);

    $uncertifiedShop = Shop::factory()->create(['status' => 'active']);
    $uncertifiedProduct = Product::factory()->published()->create([
        'shop_id' => $uncertifiedShop->id,
        'name' => 'Regular Coconut Milk',
    ]);

    $component = Livewire::test(ProductDirectory::class);
    $products = $component->instance()->products;

    expect($products->items())->toHaveCount(2);

    foreach ($products->items() as $product) {
        expect($product->relationLoaded('shop'))->toBeTrue();
        expect($product->shop->relationLoaded('certificates'))->toBeTrue();
    }

    $certifiedCard = (string) $this->blade('<x-hs-product-card :product="$product" />', ['product' => $certifiedProduct]);
    expect($certifiedCard)->toContain('absolute top-2 left-2');
    expect($certifiedCard)->toContain('Halal');

    $uncertifiedCard = (string) $this->blade('<x-hs-product-card :product="$product" />', ['product' => $uncertifiedProduct]);
    expect($uncertifiedCard)->not->toContain('absolute top-2 left-2');
    expect($uncertifiedCard)->not->toContain('Halal');
});
