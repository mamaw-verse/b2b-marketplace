<?php

declare(strict_types=1);

use App\Livewire\CompanyHub\RfqDetail;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\QuotationWithdrawnNotification;
use App\Notifications\RfqNoBidNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    if (! config('marketplace.features.seller_rfq_queue_enabled')) {
        $this->markTestSkipped('seller_rfq_queue_enabled is disabled');
    }

    $this->seed(RolePermissionSeeder::class);

    $this->buyer = User::factory()->create();
    $this->buyer->assignRole('buyer');

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $this->rfq = Rfq::factory()->create(['user_id' => $this->buyer->id]);
    $this->recipient = RfqRecipient::factory()->inProgress()->create([
        'rfq_id' => $this->rfq->id,
        'shop_id' => $this->shop->id,
    ]);
});

// ── S4.T10 — Close (replaces Decline / no_bid) ───────────────────────────────

test('the actions bar shows a Close button and no Decline button', function () {
    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->assertSee('Close')
        ->assertDontSee('Decline');
});

test('closeRfq sets the recipient status to closed', function () {
    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->set('closeReason', 'Cannot fulfil this volume.')
        ->call('closeRfq')
        ->assertSet('showCloseModal', false);

    $this->assertDatabaseHas('rfq_recipients', [
        'id' => $this->recipient->id,
        'status' => 'closed',
        'decline_reason' => 'Cannot fulfil this volume.',
    ]);
});

test('closeRfq also closes the parent RFQ when no quotation is awarded', function () {
    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('closeRfq');

    $this->assertDatabaseHas('rfqs', [
        'id' => $this->rfq->id,
        'status' => 'closed',
    ]);
    expect($this->rfq->fresh()->closed_at)->not->toBeNull();
});

test('closeRfq does not close the parent RFQ when a quotation has already been awarded', function () {
    Quotation::factory()->awarded()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('closeRfq');

    // Recipient is closed, but parent RFQ remains open (awarded quotation present).
    $this->assertDatabaseHas('rfq_recipients', [
        'id' => $this->recipient->id,
        'status' => 'closed',
    ]);
    $this->assertDatabaseHas('rfqs', [
        'id' => $this->rfq->id,
        'status' => 'open',
    ]);
});

test('closeRfq marks submitted quotations as not_awarded', function () {
    $quotation = Quotation::factory()->submitted()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('closeRfq');

    $this->assertDatabaseHas('quotations', [
        'id' => $quotation->id,
        'status' => 'not_awarded',
    ]);
});

test('closeRfq retains awarded quotations unchanged', function () {
    $awarded = Quotation::factory()->awarded()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('closeRfq');

    $this->assertDatabaseHas('quotations', [
        'id' => $awarded->id,
        'status' => 'awarded',
    ]);
});

test('closeRfq notifies the buyer with seller_closed reason', function () {
    Notification::fake();

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->set('closeReason', 'Cannot fulfil this volume.')
        ->call('closeRfq');

    Notification::assertSentTo(
        $this->buyer,
        RfqNoBidNotification::class,
        fn ($n) => $n->reason === 'seller_closed'
            && $n->rfqRecipient->decline_reason === 'Cannot fulfil this volume.'
    );
});

test('a seller cannot close an already-closed recipient', function () {
    $this->recipient->update(['status' => 'closed']);

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('closeRfq')
        ->assertForbidden();
});

// ── S4.T11 — Expandable line items + Withdraw ────────────────────────────────

test('toggleQuotation expands and collapses the line item view', function () {
    $quotation = Quotation::factory()->submitted()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('toggleQuotation', $quotation->id)
        ->assertSet("expandedQuotations.{$quotation->id}", true)
        ->call('toggleQuotation', $quotation->id)
        ->assertSet("expandedQuotations.{$quotation->id}", false);
});

test('withdrawQuotation withdraws a submitted quotation and notifies the buyer', function () {
    Notification::fake();

    $quotation = Quotation::factory()->submitted()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('withdrawQuotation', $quotation->id);

    $this->assertDatabaseHas('quotations', [
        'id' => $quotation->id,
        'status' => 'withdrawn',
    ]);

    Notification::assertSentTo($this->buyer, QuotationWithdrawnNotification::class);
});

test('a seller cannot withdraw a quotation belonging to another shop', function () {
    $otherSeller = User::factory()->create();
    $otherSeller->assignRole('seller');
    $otherShop = Shop::factory()->create(['user_id' => $otherSeller->id, 'status' => 'active']);
    $otherRecipient = RfqRecipient::factory()->inProgress()->create([
        'rfq_id' => $this->rfq->id,
        'shop_id' => $otherShop->id,
    ]);
    $foreignQuotation = Quotation::factory()->submitted()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $otherRecipient->id,
    ]);

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('withdrawQuotation', $foreignQuotation->id)
        ->assertForbidden();

    $this->assertDatabaseHas('quotations', [
        'id' => $foreignQuotation->id,
        'status' => 'submitted',
    ]);
});

test('expanded quotation shows its line item details', function () {
    $quotation = Quotation::factory()->submitted()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);
    QuotationItem::factory()->create([
        'quotation_id' => $quotation->id,
        'description' => 'Halal Coconut Flour 25kg',
        'unit' => 'sack',
    ]);

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('toggleQuotation', $quotation->id)
        ->assertSee('Halal Coconut Flour 25kg')
        ->assertSee('sack');
});
