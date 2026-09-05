<?php

declare(strict_types=1);

use App\Actions\CreateOrderAction;
use App\Models\Product;
use App\Models\ProductVariant;
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

test('order items use variant_snapshot unit but always the quotation negotiated unit_price', function () {
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
            'unit_price' => '99.99', // catalog/snapshot price — must NOT be used
        ],
        'quantity' => 2,
        'unit' => 'kg',
        'unit_price' => '10.00', // negotiated price — must be used
        'sort_order' => 0,
    ]);

    $result = (new CreateOrderAction)->execute($shop, $quotation->id);

    $orderItem = $result['order']->items->first();
    // Unit comes from snapshot; price always comes from the quotation item.
    expect($orderItem->unit)->toBe('box')
        ->and((string) $orderItem->unit_price)->toBe('10.00')
        ->and((string) $orderItem->subtotal)->toBe('20.00');
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

test('order items use negotiated quotation unit_price when variant_id is set (not catalog price)', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $product = Product::factory()->create(['shop_id' => $shop->id]);
    // Catalog price is 500.00, but the negotiated quotation price is 350.00.
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '500.00',
        'unit' => 'kg',
    ]);

    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
    $quotation = Quotation::factory()->awarded()->create([
        'rfq_id' => $rfq->id,
        'rfq_recipient_id' => $recipient->id,
    ]);

    // Priority 2: variant_id is set, no snapshot — must use quotation unit_price.
    QuotationItem::factory()->create([
        'quotation_id' => $quotation->id,
        'variant_id' => $variant->id,
        'variant_snapshot' => null,
        'description' => 'Halal Chicken Breast',
        'quantity' => 4,
        'unit' => 'kg',
        'unit_price' => '350.00',
        'sort_order' => 0,
        'product_id' => $product->id,
    ]);

    $result = (new CreateOrderAction)->execute($shop, $quotation->id);

    expect($result['success'])->toBeTrue();

    $orderItem = $result['order']->items->first();
    // Price must come from the quotation item (negotiated), not the catalog variant.
    expect((string) $orderItem->unit_price)->toBe('350.00')
        ->and($orderItem->unit)->toBe('kg')
        ->and((string) $orderItem->subtotal)->toBe('1400.00')
        ->and($orderItem->product_id)->toBe($product->id);
});

test('order item product_id is populated from the quotation item', function () {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
    $quotation = Quotation::factory()->awarded()->create([
        'rfq_id' => $rfq->id,
        'rfq_recipient_id' => $recipient->id,
    ]);

    // Freeform item that nonetheless carries a product_id reference.
    QuotationItem::factory()->create([
        'quotation_id' => $quotation->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'variant_snapshot' => null,
        'description' => 'Bulk Halal Spice Mix',
        'quantity' => 10,
        'unit' => 'bag',
        'unit_price' => '120.00',
        'sort_order' => 0,
    ]);

    $result = (new CreateOrderAction)->execute($shop, $quotation->id);

    expect($result['success'])->toBeTrue();

    $orderItem = $result['order']->items->first();
    expect($orderItem->product_id)->toBe($product->id);

    // Verify a null product_id on the quotation item also propagates as null.
    $quotation2 = Quotation::factory()->awarded()->create([
        'rfq_id' => $rfq->id,
        'rfq_recipient_id' => $recipient->id,
    ]);
    QuotationItem::factory()->create([
        'quotation_id' => $quotation2->id,
        'product_id' => null,
        'variant_id' => null,
        'variant_snapshot' => null,
        'description' => 'Custom Freeform Item',
        'quantity' => 2,
        'unit' => 'pcs',
        'unit_price' => '50.00',
        'sort_order' => 0,
    ]);

    $result2 = (new CreateOrderAction)->execute($shop, $quotation2->id);
    expect($result2['success'])->toBeTrue();
    expect($result2['order']->items->first()->product_id)->toBeNull();
});
