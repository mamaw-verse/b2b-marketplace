<?php

declare(strict_types=1);

use App\Models\OrderItem;
use App\Models\Product;

it('associates an order item with a product via product_id', function () {
    $product = Product::factory()->create();

    $item = OrderItem::factory()->create(['product_id' => $product->id]);

    expect($item->product_id)->toBe($product->id)
        ->and($item->product)->toBeInstanceOf(Product::class)
        ->and($item->product->is($product))->toBeTrue();
});

it('allows a null product_id for freeform order items', function () {
    $item = OrderItem::factory()->create(['product_id' => null]);

    expect($item->product_id)->toBeNull()
        ->and($item->product)->toBeNull();
});

it('nulls the product_id when the product is deleted', function () {
    $product = Product::factory()->create();
    $item = OrderItem::factory()->create(['product_id' => $product->id]);

    $product->forceDelete();

    expect($item->fresh()->product_id)->toBeNull();
});
