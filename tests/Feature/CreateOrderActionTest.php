<?php

declare(strict_types=1);

use App\Actions\CreateOrderAction;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;

test('order items copy unit and unit_price from quotation items', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
    $quotation = Quotation::factory()->awarded()->create([
        'rfq_id' => $rfq->id,
        'rfq_recipient_id' => $recipient->id,
    ]);

    // Freeform item (no variant, no snapshot) — unit/price must copy directly.
    QuotationItem::factory()->create([
        'quotation_id' => $quotation->id,
        'variant_id' => null,
        'variant_snapshot' => null,
        'description' => 'Halal Beef Cubes',
        'quantity' => 12,
        'unit' => 'kg',
        'unit_price' => '250.50',
        'sort_order' => 0,
    ]);

    $result = (new CreateOrderAction)->execute($shop, $quotation->id);

    expect($result['success'])->toBeTrue();

    $orderItem = $result['order']->items->first();
    expect($orderItem->unit)->toBe('kg')
        ->and((string) $orderItem->unit_price)->toBe('250.50')
        ->and((string) $orderItem->subtotal)->toBe('3006.00');
});

test('order items prefer variant_snapshot unit and unit_price when present', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
    $quotation = Quotation::factory()->awarded()->create([
        'rfq_id' => $rfq->id,
        'rfq_recipient_id' => $recipient->id,
    ]);

    QuotationItem::factory()->create([
        'quotation_id' => $quotation->id,
        'variant_id' => null,
        'variant_snapshot' => [
            'description' => 'Snapshot desc',
            'unit' => 'box',
            'unit_price' => '99.99',
        ],
        'quantity' => 2,
        'unit' => 'kg',
        'unit_price' => '10.00',
        'sort_order' => 0,
    ]);

    $result = (new CreateOrderAction)->execute($shop, $quotation->id);

    $orderItem = $result['order']->items->first();
    expect($orderItem->unit)->toBe('box')
        ->and((string) $orderItem->unit_price)->toBe('99.99');
});

test('order item description always copies the quotation item description, never the variant/snapshot description', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
    $quotation = Quotation::factory()->awarded()->create([
        'rfq_id' => $rfq->id,
        'rfq_recipient_id' => $recipient->id,
    ]);

    // Snapshot carries a different 'description' than the quotation item's own
    // composed description — the order item must keep the quotation item's text.
    QuotationItem::factory()->create([
        'quotation_id' => $quotation->id,
        'variant_id' => null,
        'variant_snapshot' => [
            'description' => 'Raw variant description fragment',
            'unit' => 'box',
            'unit_price' => '99.99',
        ],
        'description' => 'Halal Coconut Flour — Raw variant description fragment',
        'quantity' => 2,
        'unit' => 'kg',
        'unit_price' => '10.00',
        'sort_order' => 0,
    ]);

    $result = (new CreateOrderAction)->execute($shop, $quotation->id);

    $orderItem = $result['order']->items->first();
    expect($orderItem->description)->toBe('Halal Coconut Flour — Raw variant description fragment');
});
