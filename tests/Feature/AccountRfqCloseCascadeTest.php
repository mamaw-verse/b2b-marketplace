<?php

declare(strict_types=1);

use App\Livewire\Account\RfqDetail;
use App\Models\Quotation;
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

function makeRecipient(Rfq $rfq, string $status): RfqRecipient
{
    $seller = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'active']);

    return RfqRecipient::factory()->create([
        'rfq_id' => $rfq->id,
        'shop_id' => $shop->id,
        'status' => $status,
    ]);
}

// ── Cascade ───────────────────────────────────────────────────────────────────

test('closing an RFQ cascades all non-terminal recipients to closed', function () {
    $received = makeRecipient($this->rfq, 'received');
    $reviewed = makeRecipient($this->rfq, 'reviewed');
    $inProgress = makeRecipient($this->rfq, 'in_progress');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('closeRfq');

    foreach ([$received, $reviewed, $inProgress] as $recipient) {
        $this->assertDatabaseHas('rfq_recipients', [
            'id' => $recipient->id,
            'status' => 'closed',
        ]);
    }
});

test('closing an RFQ cascades recipients with awarded quotations to closed too', function () {
    $awardedRecipient = makeRecipient($this->rfq, 'in_progress');
    $awarded = Quotation::factory()->awarded()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $awardedRecipient->id,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('closeRfq');

    $this->assertDatabaseHas('rfq_recipients', [
        'id' => $awardedRecipient->id,
        'status' => 'closed',
    ]);

    // Awarded quotation itself is not touched by the recipient cascade.
    $this->assertDatabaseHas('quotations', [
        'id' => $awarded->id,
        'status' => 'awarded',
    ]);
});

test('closing an RFQ does not touch already-terminal recipients', function () {
    $alreadyClosed = makeRecipient($this->rfq, 'closed');
    $noBid = makeRecipient($this->rfq, 'no_bid');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('closeRfq');

    $this->assertDatabaseHas('rfq_recipients', ['id' => $alreadyClosed->id, 'status' => 'closed']);
    $this->assertDatabaseHas('rfq_recipients', ['id' => $noBid->id, 'status' => 'no_bid']);
});

// ── Seller-closed notice (buyer view) ─────────────────────────────────────────

test('buyer view shows seller-closed notice when rfq is open and a recipient is closed', function () {
    $recipient = makeRecipient($this->rfq, 'closed');
    $recipient->update(['decline_reason' => 'Cannot fulfil this volume within the timeline.']);

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertSee('The seller closed this RFQ.')
        ->assertSee('Cannot fulfil this volume within the timeline.');
});

test('buyer view shows seller-closed notice when a recipient status is no_bid', function () {
    makeRecipient($this->rfq, 'no_bid');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertSee('The seller closed this RFQ.')
        ->assertDontSee('Reason:');
});

test('buyer view does NOT show seller-closed notice after the buyer closes the rfq', function () {
    // When buyer closes: parent RFQ becomes 'closed', recipients become 'closed'.
    // The notice gates on rfq.isOpen() so it disappears once the buyer closes.
    makeRecipient($this->rfq, 'in_progress');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('closeRfq');

    $this->rfq->refresh();

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertDontSee('The seller closed this RFQ.');
});

// ── Close RFQ button visibility ───────────────────────────────────────────────

test('buyer view shows Close RFQ button when rfq is open and no recipient is terminal', function () {
    makeRecipient($this->rfq, 'in_progress');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertSee('Close RFQ');
});

test('buyer view hides Close RFQ button when a recipient has already been closed by the seller', function () {
    makeRecipient($this->rfq, 'closed');

    // The action-bar trigger button has wire:click="$set('showCloseConfirmation', true)".
    // It should not be rendered when any recipient is already terminal.
    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertDontSeeHtml('showCloseConfirmation\', true');
});
