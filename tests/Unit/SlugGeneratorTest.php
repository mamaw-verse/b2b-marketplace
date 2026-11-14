<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\SlugRedirect;
use App\Support\Slugs\SlugGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('generates unique slugs when names collide', function () {
    Product::factory()->create(['name' => 'Halal Chicken Nuggets']);

    expect(app(SlugGenerator::class)->generate(Product::class, 'Halal Chicken Nuggets'))
        ->toBe('halal-chicken-nuggets-2');
});

test('uses a generated fallback for numeric, empty and non-transliterable names', function (string $name) {
    $slug = app(SlugGenerator::class)->generate(Product::class, $name);

    expect($slug)->toMatch('/^product-[a-z0-9]{6}$/');
})->with([
    'numeric' => '123456',
    'empty' => '',
    'Arabic with no transliteration' => '۞۩',
]);

test('passes the current locale to slug transliteration', function () {
    app()->setLocale('tr');

    expect(app(SlugGenerator::class)->generate(Product::class, 'IĞDIR'))
        ->toBe('igdir');
});

test('uses a random fallback for reserved slugs', function () {
    config()->set('marketplace.slugs.reserved', ['reserved-name']);

    expect(app(SlugGenerator::class)->generate(Product::class, 'Reserved Name'))
        ->toMatch('/^product-[a-z0-9]{6}$/');
});

test('does not reuse slugs held by soft-deleted records', function () {
    $product = Product::factory()->create(['name' => 'Chicken Bites']);
    $product->delete();

    expect(app(SlugGenerator::class)->generate(Product::class, 'Chicken Bites'))
        ->toBe('chicken-bites-2');
});

test('does not reuse slugs held by redirects', function () {
    SlugRedirect::factory()->create([
        'sluggable_type' => Product::class,
        'slug' => 'former-product-name',
    ]);

    expect(app(SlugGenerator::class)->generate(Product::class, 'Former Product Name'))
        ->toBe('former-product-name-2');
});

test('keeps slug uniqueness scoped to each model table', function () {
    Product::factory()->create(['name' => 'Shared Name']);

    expect(app(SlugGenerator::class)->generate(Shop::class, 'Shared Name'))
        ->toBe('shared-name');
});
