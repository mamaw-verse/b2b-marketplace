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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
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

// ── S4.T10 — Rename Decline → Close ──────────────────────────────────────────

test('the actions bar shows a Close button instead of Decline', function () {
    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->assertSee('Close')
        ->assertDontSee('Decline');
});

test('closeRfq marks the recipient no_bid with the close reason', function () {
    Notification::fake();

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->set('closeReason', 'Cannot fulfil this volume.')
        ->call('closeRfq')
        ->assertSet('showCloseModal', false);

    $this->assertDatabaseHas('rfq_recipients', [
        'id' => $this->recipient->id,
        'status' => 'no_bid',
        'decline_reason' => 'Cannot fulfil this volume.',
    ]);
});

test('closeRfq withdraws submitted quotations and notifies the buyer', function () {
    Notification::fake();

    $quotation = Quotation::factory()->submitted()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);

    Livewire::actingAs($this->seller)
        ->test(RfqDetail::class, ['rfqRecipient' => $this->recipient])
        ->call('closeRfq');

    $this->assertDatabaseHas('quotations', [
        'id' => $quotation->id,
        'status' => 'withdrawn',
    ]);

    Notification::assertSentTo($this->buyer, QuotationWithdrawnNotification::class);
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
