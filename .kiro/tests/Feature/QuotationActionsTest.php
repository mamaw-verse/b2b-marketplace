<?php

declare(strict_types=1);

use App\Events\QuotationAwarded;
use App\Events\QuotationRejected;
use App\Events\RfqClosed;
use App\Models\BuyerProfile;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupAwardScenario(): array
{
    $buyer = User::factory()->create();
    BuyerProfile::factory()->create(['user_id' => $buyer->id]);
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
    $recipient = RfqRecipient::factory()->inProgress()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $shop->id,
    ]);
    $quotation = Quotation::factory()->submitted()->create([
        'rfq_id' => $rfq->id,
        'rfq_recipient_id' => $recipient->id,
    ]);
    QuotationItem::factory()->create(['quotation_id' => $quotation->id]);

    return [$buyer, $seller, $shop, $rfq, $recipient, $quotation];
}

describe('Award Quotation', function () {
    test('buyer can award a submitted quotation', function () {
        Event::fake([QuotationAwarded::class]);
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();

        $response = $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation->id}/award");

        $response->assertOk()
            ->assertJsonPath('data.status', 'awarded')
            ->assertJsonPath('data.rfq_id', $rfq->id);

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id, 'status' => 'awarded']);
        Event::assertDispatched(QuotationAwarded::class);
    });

    test('buyer cannot award an expired quotation (race condition)', function () {
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();

        // Simulate expiry between page load and click
        $quotation->update(['status' => 'expired']);

        $response = $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation->id}/award");

        $response->assertForbidden();
    });

    test('buyer cannot award a withdrawn quotation', function () {
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();
        $quotation->update(['status' => 'withdrawn']);

        $response = $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation->id}/award");

        $response->assertForbidden();
    });

    test('buyer cannot award a draft quotation', function () {
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();
        $quotation->update(['status' => 'draft']);

        $response = $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation->id}/award");

        $response->assertForbidden();
    });

    test('non-owner buyer cannot award', function () {
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();
        $otherBuyer = User::factory()->create();

        $response = $this->actingAs($otherBuyer)->postJson("/api/v1/quotations/{$quotation->id}/award");

        $response->assertForbidden();
    });

    test('awarding one quotation does not affect siblings', function () {
        Event::fake([QuotationAwarded::class]);
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation1] = setupAwardScenario();
        $quotation2 = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation1->id}/award")
            ->assertOk();

        // Sibling remains submitted
        $this->assertDatabaseHas('quotations', ['id' => $quotation2->id, 'status' => 'submitted']);
    });

    test('multiple quotations can be awarded under same RFQ', function () {
        Event::fake([QuotationAwarded::class]);
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation1] = setupAwardScenario();
        $quotation2 = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation1->id}/award")->assertOk();
        $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation2->id}/award")->assertOk();

        $this->assertDatabaseHas('quotations', ['id' => $quotation1->id, 'status' => 'awarded']);
        $this->assertDatabaseHas('quotations', ['id' => $quotation2->id, 'status' => 'awarded']);
    });
});

describe('Reject Quotation', function () {
    test('buyer can reject a submitted quotation', function () {
        Event::fake([QuotationRejected::class]);
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();

        $response = $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation->id}/reject");

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rfq_id', $rfq->id);

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id, 'status' => 'rejected']);
        Event::assertDispatched(QuotationRejected::class);
    });

    test('buyer cannot reject an already-awarded quotation', function () {
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();
        $quotation->update(['status' => 'awarded']);

        $response = $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation->id}/reject");

        $response->assertForbidden();
    });

    test('non-owner buyer cannot reject', function () {
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();
        $otherBuyer = User::factory()->create();

        $response = $this->actingAs($otherBuyer)->postJson("/api/v1/quotations/{$quotation->id}/reject");

        $response->assertForbidden();
    });
});

describe('RFQ Close Cascade', function () {
    test('closing RFQ transitions submitted quotations to not_awarded', function () {
        Event::fake([RfqClosed::class]);
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();
        $quotation2 = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/rfqs/{$rfq->id}/close");

        $response->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.quotations_not_awarded_count', 2);

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id, 'status' => 'not_awarded']);
        $this->assertDatabaseHas('quotations', ['id' => $quotation2->id, 'status' => 'not_awarded']);
        Event::assertDispatched(RfqClosed::class);
    });

    test('close does not affect already-terminal quotations', function () {
        Event::fake([RfqClosed::class]);
        [$buyer, $seller, $shop, $rfq, $recipient, $submittedQuotation] = setupAwardScenario();

        $awardedQuotation = Quotation::factory()->awarded()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);
        $rejectedQuotation = Quotation::factory()->rejected()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);
        $withdrawnQuotation = Quotation::factory()->withdrawn()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);
        $expiredQuotation = Quotation::factory()->expired()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/rfqs/{$rfq->id}/close");

        $response->assertOk()
            ->assertJsonPath('data.quotations_not_awarded_count', 1); // Only the submitted one

        // Terminal quotations remain untouched
        $this->assertDatabaseHas('quotations', ['id' => $awardedQuotation->id, 'status' => 'awarded']);
        $this->assertDatabaseHas('quotations', ['id' => $rejectedQuotation->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('quotations', ['id' => $withdrawnQuotation->id, 'status' => 'withdrawn']);
        $this->assertDatabaseHas('quotations', ['id' => $expiredQuotation->id, 'status' => 'expired']);
    });

    test('closing already-closed RFQ returns 409', function () {
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();
        $rfq->update(['status' => 'closed', 'closed_at' => now()]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/rfqs/{$rfq->id}/close");

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    });
});

describe('Race Condition Protection', function () {
    test('concurrent award on same quotation - second attempt fails', function () {
        Event::fake([QuotationAwarded::class]);
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();

        // First award succeeds
        $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation->id}/award")
            ->assertOk();

        // Second attempt on same quotation (now awarded, not submitted)
        $response = $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation->id}/award");

        $response->assertForbidden(); // Policy rejects since status is no longer 'submitted'
    });

    test('award after manual status change to expired returns appropriate error', function () {
        [$buyer, $seller, $shop, $rfq, $recipient, $quotation] = setupAwardScenario();

        // Simulate the quotation expiring between page load and award click
        // The policy will catch this since status != submitted
        $quotation->update(['status' => 'expired', 'valid_until' => now()->subDay()]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/quotations/{$quotation->id}/award");

        $response->assertForbidden();
    });
});
