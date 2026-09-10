<?php

declare(strict_types=1);

use App\Actions\CreatePayoutBatchAction;
use App\Actions\ReleasePayoutAction;
use App\Contracts\DltDisbursementServiceInterface;
use App\Models\Dispute;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Models\SellerPayoutAccount;
use App\Models\Shop;
use App\Models\User;
use App\Services\DltDisbursementService;
use Database\Seeders\PayoutSettingsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    if (! config('marketplace.features.payouts_enabled')) {
        $this->markTestSkipped('payouts_enabled is disabled');
    }

    $this->seed(RolePermissionSeeder::class);
    $this->seed(PayoutSettingsSeeder::class);
});

function createEligibleSetup(float $orderTotal = 1000.00): array
{
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    SellerPayoutAccount::factory()->verified()->create(['shop_id' => $shop->id]);

    $order = Order::factory()->completed()->create([
        'shop_id' => $shop->id,
        'order_total' => $orderTotal,
        'completed_at' => now()->subDays(10),
    ]);

    LedgerEntry::factory()->escrowHold()->create([
        'order_id' => $order->id,
        'amount' => $orderTotal,
    ]);

    return compact('seller', 'shop', 'order');
}

// ──────────────────────────────────────────────────────────────
// Eligibility Tests
// ──────────────────────────────────────────────────────────────

test('order not completed is not eligible', function () {
    $setup = createEligibleSetup();
    $setup['order']->update(['status' => 'paid', 'completed_at' => null]);

    expect(fn () => (new CreatePayoutBatchAction)->execute($setup['shop']->id))
        ->toThrow(ValidationException::class);
});

test('order already in payout_items is not eligible', function () {
    $setup = createEligibleSetup();

    // Create existing payout with this order
    $payout = Payout::factory()->create(['shop_id' => $setup['shop']->id]);
    PayoutItem::factory()->create([
        'payout_id' => $payout->id,
        'order_id' => $setup['order']->id,
        'ledger_entry_id' => LedgerEntry::where('order_id', $setup['order']->id)->first()->id,
    ]);

    expect(fn () => (new CreatePayoutBatchAction)->execute($setup['shop']->id))
        ->toThrow(ValidationException::class);
});

test('order with active dispute is not eligible', function () {
    $setup = createEligibleSetup();

    Dispute::factory()->create([
        'order_id' => $setup['order']->id,
        'status' => 'open',
    ]);

    expect(fn () => (new CreatePayoutBatchAction)->execute($setup['shop']->id))
        ->toThrow(ValidationException::class);
});

test('order completed less than dispute_hold_days ago is not eligible', function () {
    $setup = createEligibleSetup();
    $setup['order']->update(['completed_at' => now()->subDays(3)]);

    expect(fn () => (new CreatePayoutBatchAction)->execute($setup['shop']->id))
        ->toThrow(ValidationException::class);
});

test('seller payout account not verified is not eligible', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    SellerPayoutAccount::factory()->create(['shop_id' => $shop->id, 'verification_status' => 'pending']);

    $order = Order::factory()->completed()->create([
        'shop_id' => $shop->id,
        'order_total' => 1000.00,
        'completed_at' => now()->subDays(10),
    ]);

    LedgerEntry::factory()->escrowHold()->create([
        'order_id' => $order->id,
        'amount' => 1000.00,
    ]);

    expect(fn () => (new CreatePayoutBatchAction)->execute($shop->id))
        ->toThrow(ValidationException::class);
});

test('total below minimum payout threshold cannot create batch', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    SellerPayoutAccount::factory()->verified()->create(['shop_id' => $shop->id]);

    $order = Order::factory()->completed()->create([
        'shop_id' => $shop->id,
        'order_total' => 100.00,
        'completed_at' => now()->subDays(10),
    ]);

    LedgerEntry::factory()->escrowHold()->create([
        'order_id' => $order->id,
        'amount' => 100.00,
    ]);

    expect(fn () => (new CreatePayoutBatchAction)->execute($shop->id))
        ->toThrow(ValidationException::class);
});

test('all eligibility rules pass creates batch', function () {
    $setup = createEligibleSetup();

    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);

    expect($payout)->toBeInstanceOf(Payout::class)
        ->and($payout->status)->toBe('pending')
        ->and($payout->orders_count)->toBe(1)
        ->and($payout->items)->toHaveCount(1);
});

// ──────────────────────────────────────────────────────────────
// Batch Creation & Fee Calculation Tests
// ──────────────────────────────────────────────────────────────

test('batch creation creates correct payout and payout item records', function () {
    $setup = createEligibleSetup(2000.00);

    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);

    expect($payout->gross_amount)->toBe('2000.00')
        ->and($payout->orders_count)->toBe(1);

    $item = $payout->items->first();
    expect($item->escrowed_amount)->toBe('2000.00')
        ->and($item->order_id)->toBe($setup['order']->id);
});

test('fee calculation applies transaction fee flat and commission fee percentage', function () {
    $setup = createEligibleSetup(1000.00);

    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);
    $item = $payout->items->first();

    // transaction_fee = 15.00 (flat)
    expect($item->transaction_fee)->toBe('15.00');

    // commission_fee = 1000.00 * 0.03 = 30.00
    expect($item->commission_fee)->toBe('30.00');

    // net_amount = 1000.00 - 15.00 - 30.00 = 955.00
    expect($item->net_amount)->toBe('955.00');
});

test('net amount equals gross minus fees', function () {
    $setup = createEligibleSetup(5000.00);

    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);

    $expectedTxFee = 15.00;
    $expectedCommission = round(5000.00 * 0.03, 2);
    $expectedNet = round(5000.00 - $expectedTxFee - $expectedCommission, 2);

    expect((float) $payout->net_amount)->toBe($expectedNet);
});

// ──────────────────────────────────────────────────────────────
// Release Tests
// ──────────────────────────────────────────────────────────────

test('release transitions pending to processing to completed with mock success', function () {
    $setup = createEligibleSetup();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);

    $result = app(ReleasePayoutAction::class)->execute($payout, $admin);

    expect($result['success'])->toBeTrue()
        ->and($result['payout']->status)->toBe('completed')
        ->and($result['payout']->completed_at)->not->toBeNull()
        ->and($result['payout']->released_by_id)->toBe($admin->id);
});

test('release with real service failure sets status failed', function () {
    // Bind real (always-fails) service
    app()->singleton(
        DltDisbursementServiceInterface::class,
        DltDisbursementService::class,
    );

    $setup = createEligibleSetup();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);

    $result = app(ReleasePayoutAction::class)->execute($payout, $admin);

    expect($result['success'])->toBeFalse()
        ->and($result['payout']->status)->toBe('failed')
        ->and($result['payout']->failure_reason)->not->toBeNull()
        ->and($result['payout']->failed_at)->not->toBeNull();
});

test('retry failed payout transitions to completed', function () {
    $setup = createEligibleSetup();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $payout = Payout::factory()->failed()->create(['shop_id' => $setup['shop']->id]);

    $result = app(ReleasePayoutAction::class)->execute($payout, $admin);

    expect($result['success'])->toBeTrue()
        ->and($result['payout']->status)->toBe('completed');
});

test('cannot release a completed payout', function () {
    $setup = createEligibleSetup();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $payout = Payout::factory()->completed()->create(['shop_id' => $setup['shop']->id]);

    expect(fn () => app(ReleasePayoutAction::class)->execute($payout, $admin))
        ->toThrow(ValidationException::class);
});

// ──────────────────────────────────────────────────────────────
// API Authorization Tests
// ──────────────────────────────────────────────────────────────

test('non-admin cannot release payout', function () {
    $setup = createEligibleSetup();
    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);

    $this->actingAs($setup['seller'])
        ->postJson("/api/v1/admin/payouts/{$payout->id}/release")
        ->assertStatus(403);
});

test('admin without payout.release permission cannot release', function () {
    $setup = createEligibleSetup();
    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Remove payout.release from the admin role for this test
    $adminRole = Role::findByName('admin', 'web');
    $adminRole->revokePermissionTo('payout.release');
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/payouts/{$payout->id}/release")
        ->assertStatus(403);
});

test('seller can see their own payouts', function () {
    $setup = createEligibleSetup();
    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);

    $this->actingAs($setup['seller'])
        ->getJson('/api/v1/hub/payouts')
        ->assertOk()
        ->assertJsonFragment(['id' => $payout->id]);
});

test('seller cannot see another sellers payouts', function () {
    $setup = createEligibleSetup();
    $payout = (new CreatePayoutBatchAction)->execute($setup['shop']->id);

    $otherSeller = User::factory()->create();
    $otherSeller->assignRole('seller');
    $otherShop = Shop::factory()->create(['user_id' => $otherSeller->id, 'status' => 'active']);

    $this->actingAs($otherSeller)
        ->getJson("/api/v1/hub/payouts/{$payout->id}")
        ->assertStatus(403);
});

test('balance endpoint returns correct net releasable amount', function () {
    $setup = createEligibleSetup(2000.00);

    // Net = escrowed 2000.00 - transaction fee 15.00 - commission (2000 * 0.03 = 60.00) = 1925.00
    $this->actingAs($setup['seller'])
        ->getJson('/api/v1/hub/payouts/balance')
        ->assertOk()
        ->assertJsonPath('data.releasable_amount', '1925.00')
        ->assertJsonPath('data.eligible_orders_count', 1);
});
