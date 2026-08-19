<?php

declare(strict_types=1);

use App\Events\MessageSent;
use App\Events\QuotationAwarded;
use App\Events\QuotationSubmitted;
use App\Events\RfqClosed;
use App\Events\RfqCreated;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('full negotiation pipeline: buyer profile → RFQ → review → quotation → award → close', function () {
    Event::fake([RfqCreated::class, QuotationSubmitted::class, QuotationAwarded::class, RfqClosed::class, MessageSent::class]);

    // 1. Setup users
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);

    // 2. Buyer creates buyer profile
    $this->actingAs($buyer)->putJson('/api/v1/buyer-profile', [
        'business_name' => 'Pipeline Test Corp',
        'billing_address_1' => '123 Main St',
        'billing_city' => 'Manila',
        'billing_province' => 'Metro Manila',
        'billing_zip_code' => '1000',
        'shipping_address_1' => '456 Warehouse Rd',
        'shipping_city' => 'Laguna',
        'shipping_province' => 'Laguna',
        'shipping_zip_code' => '4000',
    ])->assertOk();

    // 3. Buyer sends RFQ
    $rfqResponse = $this->actingAs($buyer)->postJson('/api/v1/rfqs', [
        'title' => '1000kg Halal Coconut Oil',
        'description' => 'Monthly supply contract. FOB Manila.',
        'shop_id' => $shop->id,
    ]);
    $rfqResponse->assertCreated();
    $rfqId = $rfqResponse->json('data.id');
    $recipientId = $rfqResponse->json('data.recipients.0.id');

    Event::assertDispatched(RfqCreated::class);

    // 4. Seller reviews RFQ (calls review endpoint)
    $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipientId}/review")
        ->assertOk()
        ->assertJsonPath('data.status', 'reviewed');

    // 5. Seller marks in_progress
    $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipientId}/in-progress")
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');

    // 6. Seller creates quotation with line items
    $quotationResponse = $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipientId}/quotations", [
        'notes' => 'Monthly contract pricing. MOQ 500kg.',
        'items' => [
            ['description' => 'Virgin Coconut Oil 20L', 'quantity' => 50, 'unit' => 'drum', 'unit_price' => 4500.00],
            ['description' => 'RBD Coconut Oil 20L', 'quantity' => 30, 'unit' => 'drum', 'unit_price' => 3200.00],
        ],
    ]);
    $quotationResponse->assertCreated();
    $quotationId = $quotationResponse->json('data.id');

    expect($quotationResponse->json('data.total_amount'))->toBe('321000.00');
    expect($quotationResponse->json('data.status'))->toBe('draft');

    // 7. Seller submits quotation (becomes immutable)
    $submitResponse = $this->actingAs($seller)->postJson("/api/v1/hub/quotations/{$quotationId}/submit");
    $submitResponse->assertOk()
        ->assertJsonPath('data.status', 'submitted');

    expect($submitResponse->json('data.valid_until'))->not->toBeNull();
    expect($submitResponse->json('data.buyer_snapshot.business_name'))->toBe('Pipeline Test Corp');

    Event::assertDispatched(QuotationSubmitted::class);

    // 8. Verify immutability — seller cannot update submitted quotation
    $this->actingAs($seller)->putJson("/api/v1/hub/quotations/{$quotationId}", [
        'items' => [['description' => 'Hacked', 'quantity' => 1, 'unit' => 'x', 'unit_price' => 1]],
    ])->assertForbidden();

    // 9. Buyer views RFQ and sees the submitted quotation
    $rfqDetailResponse = $this->actingAs($buyer)->getJson("/api/v1/rfqs/{$rfqId}");
    $rfqDetailResponse->assertOk()
        ->assertJsonPath('data.status', 'open');

    // 10. Buyer awards the quotation
    $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotationId}/award")
        ->assertOk()
        ->assertJsonPath('data.status', 'awarded');

    Event::assertDispatched(QuotationAwarded::class);

    // 11. Buyer closes the RFQ
    $closeResponse = $this->actingAs($buyer)->postJson("/api/v1/rfqs/{$rfqId}/close");
    $closeResponse->assertOk()
        ->assertJsonPath('data.status', 'closed');

    Event::assertDispatched(RfqClosed::class);

    // 12. Verify final state
    $this->assertDatabaseHas('rfqs', ['id' => $rfqId, 'status' => 'closed']);
    $this->assertDatabaseHas('quotations', ['id' => $quotationId, 'status' => 'awarded']);
    $this->assertDatabaseHas('rfq_recipients', ['id' => $recipientId, 'status' => 'in_progress']);
});
