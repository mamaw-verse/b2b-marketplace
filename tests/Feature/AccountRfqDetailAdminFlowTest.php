<?php

declare(strict_types=1);

use App\Livewire\Account\RfqDetail;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->buyer = User::factory()->create();
    $this->buyer->assignRole('buyer');

    $this->rfq = Rfq::factory()->create(['user_id' => $this->buyer->id, 'status' => 'open']);
});

function makeRecipient(Rfq $rfq, string $status, ?string $adminStatus = null): RfqRecipient
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    return RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $shop->id,
        'status' => $status,
        'admin_status' => $adminStatus ?? 'pending',
    ]);
}

// ── Remove Close RFQ button ───────────────────────────────────────────────────

test('buyer RFQ detail has no Close RFQ button', function () {
    makeRecipient($this->rfq, 'in_progress');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertDontSee('showCloseConfirmation')
        ->assertDontSeeHtml('wire:click="$set(\'showCloseConfirmation\', true)"');
});

test('buyer RFQ detail has no close-rfq confirmation modal', function () {
    makeRecipient($this->rfq, 'in_progress');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertDontSee('close-rfq')
        ->assertDontSee('Closing this RFQ will mark any remaining submitted quotations');
});

// ── Remove seller-closed notice ──────────────────────────────────────────────

test('buyer RFQ detail has no seller-closed notice when a recipient is terminal', function () {
    $recipient = makeRecipient($this->rfq, 'closed');
    $recipient->update(['decline_reason' => 'Cannot fulfil this volume within the timeline.']);

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertDontSee('The seller closed this RFQ.')
        ->assertDontSee('Cannot fulfil this volume within the timeline.');
});

test('buyer RFQ detail has no seller-closed notice for a no_bid recipient', function () {
    makeRecipient($this->rfq, 'no_bid');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertDontSee('The seller closed this RFQ.');
});

// ── Remove recipient info line ───────────────────────────────────────────────

test('buyer RFQ detail does not show the recipient business name', function () {
    $seller = User::factory()->create();
    $shop = Shop::factory()->create([
        'user_id' => $seller->id,
        'status' => 'active',
        'storefront_name' => 'Acme Food Trading Store',
    ]);

    RfqRecipient::factory()->create([
        'rfq_id' => $this->rfq->id,
        'shop_id' => $shop->id,
        'status' => 'in_progress',
        'admin_status' => 'pending',
    ]);

    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.show', $this->rfq))
        ->assertOk()
        ->assertDontSee('Acme Food Trading Store');
});

// ── Admin status reflects admin actions ───────────────────────────────────────

test('buyer RFQ detail reflects the admin status even when the RFQ is closed', function () {
    $this->rfq->update(['status' => 'closed']);
    makeRecipient($this->rfq, 'in_progress', 'resolved');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertSee('Resolved')
        ->assertDontSee('The seller closed this RFQ.');
});
