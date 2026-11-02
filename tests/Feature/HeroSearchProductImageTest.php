<?php

declare(strict_types=1);

use App\Livewire\Storefront\HeroSearch;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Scout\EngineManager;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('scout.driver', 'pgsql');
    Config::set('marketplace.features.subscriptions_enabled', false);
    app(EngineManager::class)->forgetEngines();
});

it('returns product models with featured image url accessor', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Amber Forest Honey',
        'featured_image_path' => 'products/honey-amber.jpg',
    ]);

    $component = Livewire\Livewire::test(HeroSearch::class)
        ->set('query', 'honey')
        ->call('search');

    $results = $component->get('productResults');

    expect($results)->not->toBeEmpty();

    /** @var Product $result */
    $result = $results->first();
    expect($result)->toBeInstanceOf(Product::class)
        ->and($result->id)->toBe($product->id)
        ->and($result->featured_image_url)->not->toBeNull()
        ->and($result->featured_image_url)->toBe(route('products.featured-image', $product));
});

it('returns product models with shop relation loaded', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'storefront_name' => 'Golden Honey Co',
    ]);
    Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Mountain Honey',
    ]);

    $component = Livewire\Livewire::test(HeroSearch::class)
        ->set('query', 'honey')
        ->call('search');

    /** @var Product $result */
    $result = $component->get('productResults')->first();
    expect($result->relationLoaded('shop'))->toBeTrue()
        ->and($result->shop->storefront_name)->toBe('Golden Honey Co');
});

it('renders featured image img tag when product has image', function () {
    $shop = Shop::factory()->create(['status' => 'active']);
    Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Wildflower Honey',
        'featured_image_path' => 'products/wildflower.jpg',
    ]);

    $component = Livewire\Livewire::test(HeroSearch::class)
        ->set('query', 'wildflower')
        ->call('search')
        ->assertSee('Wildflower Honey');

    // The hs-product-card renders an <img> when featured_image_url is not null
    $component->assertSee('<img', false);
});

it('renders product card with shop name from relation not array', function () {
    $shop = Shop::factory()->create([
        'status' => 'active',
        'storefront_name' => 'Desert Apiary',
    ]);
    Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Date Honey Premium',
    ]);

    Livewire\Livewire::test(HeroSearch::class)
        ->set('query', 'date honey')
        ->call('search')
        ->assertSee('Date Honey Premium')
        ->assertSee('Desert Apiary');
});
