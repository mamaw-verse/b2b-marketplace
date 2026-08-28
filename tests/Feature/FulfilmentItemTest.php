<?php

declare(strict_types=1);

use App\Models\Fulfilment;
use App\Models\FulfilmentItem;
use App\Models\OrderItem;

it('belongs to a fulfilment and an order item', function () {
    $fulfilment = Fulfilment::factory()->create();
    $orderItem = OrderItem::factory()->create();

    $item = FulfilmentItem::factory()->create([
        'fulfilment_id' => $fulfilment->id,
        'order_item_id' => $orderItem->id,
        'quantity' => 12.50,
    ]);

    expect($item->fulfilment->is($fulfilment))->toBeTrue()
        ->and($item->orderItem->is($orderItem))->toBeTrue()
        ->and($item->quantity)->toBe('12.50');
});

it('exposes fulfilment items through the hasMany relation', function () {
    $fulfilment = Fulfilment::factory()->create();
    FulfilmentItem::factory()->count(3)->create(['fulfilment_id' => $fulfilment->id]);

    expect($fulfilment->fulfilmentItems)->toHaveCount(3)
        ->and($fulfilment->fulfilmentItems->first())->toBeInstanceOf(FulfilmentItem::class);
});

it('cascades deletion of fulfilment items when the fulfilment is deleted', function () {
    $fulfilment = Fulfilment::factory()->create();
    $item = FulfilmentItem::factory()->create(['fulfilment_id' => $fulfilment->id]);

    $fulfilment->delete();

    expect(FulfilmentItem::find($item->id))->toBeNull();
});
