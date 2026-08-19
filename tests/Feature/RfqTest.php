<?php

declare(strict_types=1);

use App\Events\RfqCreated;
use App\Models\BuyerProfile;
use App\Models\Rfq;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('RFQ Creation', function () {
    test('buyer with profile can create RFQ', function () {
        Event::fake([RfqCreated::class]);

        $buyer = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $buyer->id, 'business_name' => 'Test Buyer Co']);
        $shop = Shop::factory()->create();

        $response = $this->actingAs($buyer)->postJson('/api/v1/rfqs', [
            'title' => '500kg Halal Coconut Flour',
            'description' => 'Need bulk pricing for monthly supply.',
            'shop_id' => $shop->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', '500kg Halal Coconut Flour')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.buyer_snapshot.business_name', 'Test Buyer Co')
            ->assertJsonPath('data.recipients.0.shop_id', $shop->id)
            ->assertJsonPath('data.recipients.0.status', 'received');

        $this->assertDatabaseHas('rfqs', [
            'user_id' => $buyer->id,
            'title' => '500kg Halal Coconut Flour',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('rfq_recipients', [
            'shop_id' => $shop->id,
            'status' => 'received',
        ]);

        Event::assertDispatched(RfqCreated::class);
    });

    test('buyer without profile cannot create RFQ', function () {
        $buyer = User::factory()->create();
        $shop = Shop::factory()->create();

        $response = $this->actingAs($buyer)->postJson('/api/v1/rfqs', [
            'title' => 'Test RFQ',
            'shop_id' => $shop->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJson(fn ($json) => $json->has('error.fields.buyer_profile'));
    });

    test('shop_id must reference an active shop', function () {
        $buyer = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $buyer->id]);
        $inactiveShop = Shop::factory()->inactive()->create();

        $response = $this->actingAs($buyer)->postJson('/api/v1/rfqs', [
            'title' => 'Test RFQ',
            'shop_id' => $inactiveShop->id,
        ]);

        $response->assertUnprocessable()
            ->assertJson(fn ($json) => $json->has('error.fields.shop_id'));
    });

    test('unauthenticated user cannot create RFQ', function () {
        $this->postJson('/api/v1/rfqs', ['title' => 'Test'])->assertUnauthorized();
    });

    test('RFQ captures buyer snapshot at creation time', function () {
        Event::fake([RfqCreated::class]);

        $buyer = User::factory()->create();
        $profile = BuyerProfile::factory()->create([
            'user_id' => $buyer->id,
            'business_name' => 'Snapshot Corp',
            'billing_city' => 'Manila',
            'shipping_city' => 'Cebu',
        ]);
        $shop = Shop::factory()->create();

        $response = $this->actingAs($buyer)->postJson('/api/v1/rfqs', [
            'title' => 'Snapshot test',
            'shop_id' => $shop->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.buyer_snapshot.business_name', 'Snapshot Corp')
            ->assertJsonPath('data.buyer_snapshot.billing_address.city', 'Manila')
            ->assertJsonPath('data.buyer_snapshot.shipping_address.city', 'Cebu');
    });
});

describe('RFQ Listing', function () {
    test('buyer can list their RFQs', function () {
        $buyer = User::factory()->create();
        Rfq::factory()->count(3)->create(['user_id' => $buyer->id]);

        $response = $this->actingAs($buyer)->getJson('/api/v1/rfqs');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'created_at']],
                'meta' => ['next_cursor', 'prev_cursor', 'per_page'],
            ]);
    });

    test('buyer only sees their own RFQs', function () {
        $buyer1 = User::factory()->create();
        $buyer2 = User::factory()->create();
        Rfq::factory()->count(2)->create(['user_id' => $buyer1->id]);
        Rfq::factory()->count(3)->create(['user_id' => $buyer2->id]);

        $response = $this->actingAs($buyer1)->getJson('/api/v1/rfqs');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('RFQ Detail', function () {
    test('buyer can view their own RFQ detail', function () {
        $buyer = User::factory()->create();
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);

        $response = $this->actingAs($buyer)->getJson("/api/v1/rfqs/{$rfq->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $rfq->id)
            ->assertJsonPath('data.title', $rfq->title);
    });

    test('buyer cannot view another buyer\'s RFQ', function () {
        $buyer1 = User::factory()->create();
        $buyer2 = User::factory()->create();
        $rfq = Rfq::factory()->create(['user_id' => $buyer2->id]);

        $response = $this->actingAs($buyer1)->getJson("/api/v1/rfqs/{$rfq->id}");

        $response->assertForbidden();
    });
});

describe('RFQ Close', function () {
    test('buyer can close their open RFQ', function () {
        $buyer = User::factory()->create();
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id, 'status' => 'open']);

        $response = $this->actingAs($buyer)->postJson("/api/v1/rfqs/{$rfq->id}/close");

        $response->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJson(fn ($json) => $json->has('data.closed_at'));

        $this->assertDatabaseHas('rfqs', ['id' => $rfq->id, 'status' => 'closed']);
    });

    test('closing already-closed RFQ returns 409', function () {
        $buyer = User::factory()->create();
        $rfq = Rfq::factory()->closed()->create(['user_id' => $buyer->id]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/rfqs/{$rfq->id}/close");

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    });

    test('buyer cannot close another buyer\'s RFQ', function () {
        $buyer1 = User::factory()->create();
        $buyer2 = User::factory()->create();
        $rfq = Rfq::factory()->create(['user_id' => $buyer2->id]);

        $response = $this->actingAs($buyer1)->postJson("/api/v1/rfqs/{$rfq->id}/close");

        $response->assertForbidden();
    });
});
