<?php

declare(strict_types=1);

use App\Events\DisputeEscalated;
use App\Events\DisputeOpened;
use App\Events\DisputeResolved;
use App\Jobs\OrderAutoAcceptJob;
use App\Models\Dispute;
use App\Models\Fulfilment;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPaymentTerm;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupDisputeScenario(): array
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
    $buyer = User::factory()->create();
    $order = Order::factory()->paid()->create([
        'buyer_user_id' => $buyer->id,
        'shop_id' => $shop->id,
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);
    OrderPaymentTerm::factory()->create(['order_id' => $order->id, 'amount' => $order->order_total]);
    LedgerEntry::factory()->escrowHold()->create(['order_id' => $order->id, 'amount' => $order->order_total]);
    Fulfilment::factory()->delivered()->create(['order_id' => $order->id]);

    return [$seller, $shop, $buyer, $order];
}

describe('Disputes', function () {
    test('buyer can open refund_only dispute on delivered order', function () {
        Event::fake([DisputeOpened::class]);

        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/disputes", [
            'type' => 'refund_only',
            'reason' => 'Product arrived damaged',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'open');
        $response->assertJsonPath('data.type', 'refund_only');

        Event::assertDispatched(DisputeOpened::class);
    });

    test('buyer can open return_refund dispute on delivered order', function () {
        Event::fake([DisputeOpened::class]);

        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/disputes", [
            'type' => 'return_refund',
            'reason' => 'Wrong product received',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'awaiting_return');
        $response->assertJsonPath('data.type', 'return_refund');

        Event::assertDispatched(DisputeOpened::class);
    });

    test('buyer cannot open dispute before delivery', function () {
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);
        $buyer = User::factory()->create();
        $order = Order::factory()->paid()->create([
            'buyer_user_id' => $buyer->id,
            'shop_id' => $shop->id,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);
        Fulfilment::factory()->create(['order_id' => $order->id, 'status' => 'pending']);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/disputes", [
            'type' => 'refund_only',
            'reason' => 'Product not as described',
        ]);

        $response->assertStatus(403);
    });

    test('buyer cannot open dispute if one already active', function () {
        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'open',
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/disputes", [
            'type' => 'refund_only',
            'reason' => 'Another issue',
        ]);

        $response->assertStatus(403);
    });

    test('seller can confirm return', function () {
        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'return_refund',
            'status' => 'awaiting_return',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/disputes/{$dispute->id}/confirm-return");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'return_received');
    });

    test('seller can resolve with full refund', function () {
        Event::fake([DisputeResolved::class]);

        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'open',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/disputes/{$dispute->id}/resolve", [
            'resolution_type' => 'full_refund',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'resolved_refunded');

        $order->refresh();
        expect($order->status)->toBe('refunded');

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'type' => 'refund',
        ]);

        Event::assertDispatched(DisputeResolved::class);
    });

    test('seller can resolve with partial refund', function () {
        Event::fake([DisputeResolved::class]);

        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'open',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/disputes/{$dispute->id}/resolve", [
            'resolution_type' => 'partial_refund',
            'resolution_amount' => 5000,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'type' => 'refund',
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'type' => 'payout',
        ]);

        Event::assertDispatched(DisputeResolved::class);
    });

    test('seller can deny dispute', function () {
        Event::fake([DisputeResolved::class]);

        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'open',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/disputes/{$dispute->id}/resolve", [
            'resolution_type' => 'denied',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'resolved_denied');

        $order->refresh();
        expect($order->status)->toBe('completed');

        Event::assertDispatched(DisputeResolved::class);
    });

    test('seller cannot resolve return_refund before return_received', function () {
        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'return_refund',
            'status' => 'awaiting_return',
        ]);

        $response = $this->actingAs($seller)->postJson("/api/v1/hub/orders/{$order->id}/disputes/{$dispute->id}/resolve", [
            'resolution_type' => 'full_refund',
        ]);

        $response->assertForbidden();
    });

    test('either party can escalate dispute', function () {
        Event::fake([DisputeEscalated::class]);

        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'open',
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/disputes/{$dispute->id}/escalate");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'escalated');

        Event::assertDispatched(DisputeEscalated::class);
    });

    test('admin can arbitrate escalated dispute', function () {
        Event::fake([DisputeResolved::class]);

        Spatie\Permission\Models\Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'escalated',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/disputes/{$dispute->id}/arbitrate", [
            'resolution_type' => 'full_refund',
            'resolution_notes' => 'Buyer provided sufficient evidence of damage.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'resolved_refunded');

        Event::assertDispatched(DisputeResolved::class);
    });

    test('admin cannot arbitrate non-escalated dispute', function () {
        Spatie\Permission\Models\Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/v1/admin/disputes/{$dispute->id}/arbitrate", [
            'resolution_type' => 'full_refund',
            'resolution_notes' => 'Attempting to arbitrate too early.',
        ]);

        $response->assertStatus(403);
    });

    test('buyer can withdraw dispute', function () {
        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        $dispute = Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'open',
        ]);

        $response = $this->actingAs($buyer)->postJson("/api/v1/orders/{$order->id}/disputes/{$dispute->id}/withdraw");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'withdrawn');
    });

    test('auto-accept skips orders with active disputes', function () {
        [$seller, $shop, $buyer, $order] = setupDisputeScenario();

        // Backdate the fulfilment delivery to 8 days ago
        $order->fulfilments()->update(['delivered_at' => now()->subDays(8)]);

        Dispute::factory()->create([
            'order_id' => $order->id,
            'opened_by_user_id' => $buyer->id,
            'type' => 'refund_only',
            'status' => 'open',
        ]);

        (new OrderAutoAcceptJob)->handle(app(App\Actions\ConfirmReceiptAction::class));

        $order->refresh();
        expect($order->status)->toBe('paid');
    });
});
