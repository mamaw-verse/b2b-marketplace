<?php

declare(strict_types=1);

use App\Actions\ResolveDisputeAction;
use App\Models\Dispute;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\PayoutSetting;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $this->order = Order::factory()->paid()->create([
        'shop_id' => $this->shop->id,
        'order_total' => '1000.00',
    ]);
});

test('a return_refund dispute cannot be refunded before the return is received', function () {
    $dispute = Dispute::factory()->create([
        'order_id' => $this->order->id,
        'opened_by_user_id' => $this->order->buyer_user_id,
        'type' => 'return_refund',
        'status' => 'awaiting_return',
    ]);

    $result = (new ResolveDisputeAction)->execute($dispute, $this->seller, [
        'resolution_type' => 'full_refund',
    ]);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Confirm the return');
    expect($dispute->fresh()->status)->toBe('awaiting_return');
});

test('a return_refund dispute can be denied before the return is received', function () {
    $dispute = Dispute::factory()->create([
        'order_id' => $this->order->id,
        'opened_by_user_id' => $this->order->buyer_user_id,
        'type' => 'return_refund',
        'status' => 'awaiting_return',
    ]);

    $result = (new ResolveDisputeAction)->execute($dispute, $this->seller, [
        'resolution_type' => 'denied',
        'resolution_notes' => 'Return window expired.',
    ]);

    expect($result['success'])->toBeTrue();
    expect($dispute->fresh()->status)->toBe('resolved_denied');
    expect($this->order->fresh()->status)->toBe('completed');
});

test('partial refund payout uses the PayoutSetting commission rate', function () {
    PayoutSetting::query()->updateOrCreate(['key' => 'commission_fee_rate'], ['value' => '0.10']);

    $dispute = Dispute::factory()->create([
        'order_id' => $this->order->id,
        'opened_by_user_id' => $this->order->buyer_user_id,
        'type' => 'refund_only',
        'status' => 'open',
    ]);

    $result = (new ResolveDisputeAction)->execute($dispute, $this->seller, [
        'resolution_type' => 'partial_refund',
        'resolution_amount' => 400.0,
    ]);

    expect($result['success'])->toBeTrue();

    // Remainder 600 * (1 - 0.10) = 540.00
    $payout = LedgerEntry::where('order_id', $this->order->id)->where('type', 'payout')->first();
    expect($payout)->not->toBeNull()
        ->and((float) $payout->amount)->toBe(540.0);

    $refund = LedgerEntry::where('order_id', $this->order->id)->where('type', 'refund')->first();
    expect((float) $refund->amount)->toBe(400.0);
});
