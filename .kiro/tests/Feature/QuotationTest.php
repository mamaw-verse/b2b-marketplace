<?php

declare(strict_types=1);

use App\Events\QuotationSubmitted;
use App\Models\BuyerProfile;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createSellerWithRfq(): array
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    BuyerProfile::factory()->create(['user_id' => $buyer->id]);
    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    $recipient = RfqRecipient::factory()->inProgress()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $shop->id,
    ]);

    return [$seller, $shop, $buyer, $rfq, $recipient];
}

describe('Quotation Creation', function () {
    test('seller can create draft quotation for their rfq_recipient', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/quotations", [
            'notes' => 'Valid for 30 days.',
            'items' => [
                ['description' => 'Coconut Flour 25kg', 'quantity' => 20, 'unit' => 'sack', 'unit_price' => 3500.00],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.notes', 'Valid for 30 days.')
            ->assertJsonPath('data.currency', 'PHP')
            ->assertJsonPath('data.total_amount', '70000.00')
            ->assertJsonPath('data.items.0.description', 'Coconut Flour 25kg')
            ->assertJsonPath('data.items.0.subtotal', '70000.00');

        $this->assertDatabaseHas('quotations', [
            'rfq_recipient_id' => $recipient->id,
            'status' => 'draft',
        ]);
    });

    test('seller cannot create quotation for another shop', function () {
        $seller = User::factory()->create();
        Shop::factory()->create(['user_id' => $seller->id]);
        $otherShop = Shop::factory()->create();
        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $otherShop->id]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/quotations", [
            'items' => [['description' => 'Test', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 100]],
        ]);

        $response->assertForbidden();
    });

    test('multiple line items are supported', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/quotations", [
            'items' => [
                ['description' => 'Item A', 'quantity' => 10, 'unit' => 'kg', 'unit_price' => 100],
                ['description' => 'Item B', 'quantity' => 5, 'unit' => 'box', 'unit_price' => 500],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.total_amount', '3500.00');
    });
});

describe('Quotation Update', function () {
    test('seller can update draft quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
            'status' => 'draft',
        ]);
        QuotationItem::factory()->create(['quotation_id' => $quotation->id]);

        $response = $this->actingAs($seller)->putJson("/api/v1/hub/quotations/{$quotation->id}", [
            'notes' => 'Updated notes',
            'items' => [
                ['description' => 'New Item', 'quantity' => 50, 'unit' => 'pallet', 'unit_price' => 2000],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.notes', 'Updated notes')
            ->assertJsonPath('data.total_amount', '100000.00')
            ->assertJsonCount(1, 'data.items');
    });

    test('seller cannot update submitted quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        $response = $this->actingAs($seller)->putJson("/api/v1/hub/quotations/{$quotation->id}", [
            'items' => [['description' => 'Test', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 1]],
        ]);

        $response->assertForbidden();
    });
});

describe('Quotation Delete', function () {
    test('seller can delete draft quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($seller)->deleteJson("/api/v1/hub/quotations/{$quotation->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('quotations', ['id' => $quotation->id]);
    });

    test('seller cannot delete submitted quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        $response = $this->actingAs($seller)->deleteJson("/api/v1/hub/quotations/{$quotation->id}");

        $response->assertForbidden();
    });
});

describe('Quotation Submit', function () {
    test('submit with items sets valid_until, submitted_at, buyer_snapshot', function () {
        Event::fake([QuotationSubmitted::class]);

        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
            'status' => 'draft',
        ]);
        QuotationItem::factory()->create(['quotation_id' => $quotation->id, 'subtotal' => 5000]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/quotations/{$quotation->id}/submit");

        $response->assertOk()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJson(fn ($json) => $json->has('data.submitted_at'))
            ->assertJson(fn ($json) => $json->has('data.valid_until'))
            ->assertJson(fn ($json) => $json->has('data.buyer_snapshot'));

        $quotation->refresh();
        expect($quotation->status)->toBe('submitted');
        expect($quotation->submitted_at)->not->toBeNull();
        expect($quotation->valid_until)->not->toBeNull();
        expect($quotation->buyer_snapshot)->not->toBeNull();

        Event::assertDispatched(QuotationSubmitted::class);
    });

    test('submit with zero items returns 422', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/quotations/{$quotation->id}/submit");

        $response->assertForbidden(); // Policy checks items count
    });

    test('submitted quotation is immutable', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        $response = $this->actingAs($seller)->putJson("/api/v1/hub/quotations/{$quotation->id}", [
            'items' => [['description' => 'Hacked', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 1]],
        ]);

        $response->assertForbidden();
    });
});

describe('Quotation Withdraw', function () {
    test('seller can withdraw submitted quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/quotations/{$quotation->id}/withdraw");

        $response->assertOk()
            ->assertJsonPath('data.status', 'withdrawn');

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id, 'status' => 'withdrawn']);
    });

    test('cannot withdraw non-submitted quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/quotations/{$quotation->id}/withdraw");

        $response->assertForbidden();
    });

    test('cannot withdraw awarded quotation', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();
        $quotation = Quotation::factory()->awarded()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/quotations/{$quotation->id}/withdraw");

        $response->assertForbidden();
    });
});

describe('Quotation total_amount calculation', function () {
    test('total_amount is correctly computed from item subtotals', function () {
        [$seller, $shop, $buyer, $rfq, $recipient] = createSellerWithRfq();

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/quotations", [
            'items' => [
                ['description' => 'A', 'quantity' => 10, 'unit' => 'kg', 'unit_price' => 100.50],
                ['description' => 'B', 'quantity' => 3, 'unit' => 'box', 'unit_price' => 250.00],
            ],
        ]);

        $response->assertCreated();
        // 10 * 100.50 = 1005.00 + 3 * 250 = 750.00 = 1755.00
        $response->assertJsonPath('data.total_amount', '1755.00');
    });
});
