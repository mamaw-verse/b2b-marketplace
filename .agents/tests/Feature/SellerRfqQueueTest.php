<?php

declare(strict_types=1);

use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Seller RFQ Queue - API', function () {
    test('seller can list received RFQs for their shop', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create();
        RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/rfqs');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rfq_id', $rfq->id)
            ->assertJsonPath('data.0.status', 'received');
    });

    test('seller cannot see RFQs for another shop', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $otherShop = Shop::factory()->create();
        $rfq = Rfq::factory()->create();
        RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $otherShop->id]);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/rfqs');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    test('seller can filter RFQs by status', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq1 = Rfq::factory()->create();
        $rfq2 = Rfq::factory()->create();
        RfqRecipient::factory()->create(['rfq_id' => $rfq1->id, 'shop_id' => $shop->id, 'status' => 'received']);
        RfqRecipient::factory()->create(['rfq_id' => $rfq2->id, 'shop_id' => $shop->id, 'status' => 'reviewed']);

        $response = $this->actingAs($seller)->getJson('/api/v1/hub/rfqs?status=received');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'received');
    });

    test('seller can view RFQ detail', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);

        $response = $this->actingAs($seller)->getJson("/api/v1/hub/rfqs/{$recipient->id}");

        $response->assertOk()
            ->assertJsonPath('data.rfq_recipient_id', $recipient->id)
            ->assertJsonPath('data.title', $rfq->title)
            ->assertJson(fn ($json) => $json->has('data.buyer_snapshot'));
    });

    test('seller cannot view RFQ detail for another shop', function () {
        $seller = User::factory()->create();
        Shop::factory()->create(['user_id' => $seller->id]);
        $otherShop = Shop::factory()->create();
        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $otherShop->id]);

        $response = $this->actingAs($seller)->getJson("/api/v1/hub/rfqs/{$recipient->id}");

        $response->assertForbidden();
    });
});

describe('Seller RFQ Status Transitions - API', function () {
    test('review transitions received to reviewed', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
            'status' => 'received',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/review");

        $response->assertOk()
            ->assertJsonPath('data.status', 'reviewed')
            ->assertJson(fn ($json) => $json->has('data.reviewed_at'));

        $this->assertDatabaseHas('rfq_recipients', ['id' => $recipient->id, 'status' => 'reviewed']);
    });

    test('review on already-reviewed RFQ returns 409', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->reviewed()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/review");

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    });

    test('in-progress transitions from reviewed', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->reviewed()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/in-progress");

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('rfq_recipients', ['id' => $recipient->id, 'status' => 'in_progress']);
    });

    test('decline sets status to no_bid with reason', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->reviewed()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/decline", [
            'decline_reason' => 'Cannot fulfil this volume.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'no_bid')
            ->assertJsonPath('data.decline_reason', 'Cannot fulfil this volume.');

        $this->assertDatabaseHas('rfq_recipients', [
            'id' => $recipient->id,
            'status' => 'no_bid',
            'decline_reason' => 'Cannot fulfil this volume.',
        ]);
    });

    test('cannot transition from no_bid to any other state', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->noBid()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
        ]);

        $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/review")->assertForbidden();
        $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/in-progress")->assertForbidden();
        $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/decline")->assertForbidden();
    });

    test('last_activity_at updates on each transition', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create(['last_activity_at' => now()->subDays(5)]);
        $recipient = RfqRecipient::factory()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
            'status' => 'received',
            'last_activity_at' => now()->subDays(5),
        ]);

        $this->actingAs($seller)->postJson("/api/v1/hub/rfqs/{$recipient->id}/review");

        $recipient->refresh();
        $rfq->refresh();

        expect($recipient->last_activity_at->diffInMinutes(now()))->toBeLessThan(1);
        expect($rfq->last_activity_at->diffInMinutes(now()))->toBeLessThan(1);
    });

    test('unauthenticated user cannot access seller RFQ endpoints', function () {
        $this->getJson('/api/v1/hub/rfqs')->assertUnauthorized();
    });
});
