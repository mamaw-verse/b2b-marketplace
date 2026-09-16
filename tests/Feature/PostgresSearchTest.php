<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Config;
use Laravel\Scout\EngineManager;

beforeEach(function () {
    Config::set('scout.driver', 'pgsql');
    Config::set('marketplace.features.subscriptions_enabled', false);
    app(EngineManager::class)->forgetEngines();
});

test('finds shops by storefront name and excludes inactive shops', function () {
    Shop::factory()->create([
        'storefront_name' => 'Al Barakah Halal Foods',
        'registered_business_name' => 'Al Barakah Foods Inc',
        'status' => 'active',
    ]);
    Shop::factory()->create(['storefront_name' => 'Manila Noodle House', 'status' => 'active']);
    Shop::factory()->inactive()->create(['storefront_name' => 'Al Barakah Copycat']);

    $results = Shop::search('Barakah')->get();

    expect($results->pluck('storefront_name'))
        ->toContain('Al Barakah Halal Foods')
        ->not->toContain('Manila Noodle House')
        ->not->toContain('Al Barakah Copycat');
});

test('matches shop prefixes and typos', function () {
    Shop::factory()->create(['storefront_name' => 'Majestic Halal Meat Co', 'status' => 'active']);

    expect(Shop::search('Majes')->get()->pluck('storefront_name'))
        ->toContain('Majestic Halal Meat Co');

    expect(Shop::search('Majestik Halal Meat Co')->get()->pluck('storefront_name'))
        ->toContain('Majestic Halal Meat Co');
});

test('filters shops by country, province, and category', function () {
    $category = Category::factory()->create(['name' => 'Beverages']);

    $match = Shop::factory()->create([
        'storefront_name' => 'Cebu Halal Drinks',
        'city' => 'Cebu City',
        'province' => 'Cebu',
        'country_code' => 'PH',
        'status' => 'active',
    ]);
    $match->categories()->attach($category->id);

    $other = Shop::factory()->create([
        'storefront_name' => 'Cebu Halal Drinks Depot',
        'country_code' => 'MY',
        'status' => 'active',
    ]);

    expect(Shop::search('Drinks')->where('country_code', 'PH')->get()->pluck('id'))
        ->toContain($match->id)
        ->not->toContain($other->id);

    expect(Shop::search('Drinks')->where('province', 'Cebu')->where('country_code', 'PH')->get()->pluck('id'))
        ->toContain($match->id);

    expect(Shop::search('Drinks')->where('categories', 'Beverages')->where('country_code', 'PH')->get()->pluck('id'))
        ->toContain($match->id);
});

test('blank query returns scoped shops with pagination', function () {
    Shop::factory()->count(3)->create(['status' => 'active']);
    Shop::factory()->inactive()->create();

    $results = Shop::search('')->paginate(2);

    expect($results->total())->toBe(3);
    expect($results->items())->toHaveCount(2);
});

test('finds products by name and description, excluding hidden products', function () {
    $shop = Shop::factory()->create(['status' => 'active']);

    $visible = Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Organic Coconut Oil',
        'description' => 'Cold-pressed halal cooking oil',
    ]);
    Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Organic Coconut Oil Copy', 'status' => 'archived']);
    Product::factory()->published()->flagged()->create(['shop_id' => $shop->id, 'name' => 'Organic Coconut Oil Flagged']);

    $ids = Product::search('coconut')->get()->pluck('id');

    expect($ids)->toContain($visible->id)->toHaveCount(1);
});

test('matches products by shop name and category filter', function () {
    $category = Category::factory()->create(['name' => 'Oils']);
    $shop = Shop::factory()->create([
        'storefront_name' => 'Barakah Pantry',
        'registered_business_name' => 'Barakah Pantry Inc',
        'status' => 'active',
    ]);
    $product = Product::factory()->published()->create(['shop_id' => $shop->id, 'name' => 'Plain Rice']);
    $product->categories()->attach($category->id);

    expect(Product::search('Barakah')->get()->pluck('id'))->toContain($product->id);

    expect(Product::search('Rice')->where('categories', 'Oils')->get()->pluck('id'))
        ->toContain($product->id);

    expect(Product::search('Rice')->where('shop_id', (string) $shop->id)->get()->pluck('id'))
        ->toContain($product->id);
});

test('respects the subscription gate when enabled', function () {
    Config::set('marketplace.features.subscriptions_enabled', true);

    $shop = Shop::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Gated Coconut Sugar',
    ]);

    expect(Product::search('coconut sugar')->get())->toBeEmpty();

    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'subscription_plan_id' => $plan->id,
        'status' => 'active',
    ]);

    expect(Product::search('coconut sugar')->get()->pluck('id'))->toContain($product->id);
});
