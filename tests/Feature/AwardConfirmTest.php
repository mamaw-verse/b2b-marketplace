<?php

declare(strict_types=1);

use App\Livewire\Account\RfqDetail;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->buyer = User::factory()->create(['email_verified_at' => now()]);
    $this->buyer->assignRole('buyer');

    $this->seller = User::factory()->create();
    $this->seller->assignRole('seller');
    $this->shop = Shop::factory()->create(['user_id' => $this->seller->id, 'status' => 'active']);

    $this->rfq = Rfq::factory()->create(['user_id' => $this->buyer->id]);
    $this->recipient = RfqRecipient::factory()->inProgress()->create([
        'rfq_id' => $this->rfq->id,
        'shop_id' => $this->shop->id,
    ]);
    $this->quotation = Quotation::factory()->submitted()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $this->recipient->id,
    ]);
});

test('confirmAwardQuotation sets the confirming id property', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('confirmAwardQuotation', $this->quotation->id)
        ->assertSet('confirmingAwardQuotationId', $this->quotation->id);
});

test('the award confirm modal is present in the blade', function () {
    $this->actingAs($this->buyer)
        ->get(route('account.rfqs.show', $this->rfq))
        ->assertOk()
        ->assertSee('confirm-award-quotation', false)
        ->assertSee('Award this quotation');
});

test('viewQuotation opens the detail modal for the selected quotation', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertSet('showQuotationDetail', false)
        ->call('viewQuotation', $this->quotation->id)
        ->assertSet('viewingQuotationId', $this->quotation->id)
        ->assertSet('showQuotationDetail', true);
});

test('the quotation detail modal renders the selected quotation line items', function () {
    QuotationItem::factory()->create([
        'quotation_id' => $this->quotation->id,
        'description' => 'Halal Coconut Flour 25kg',
        'unit' => 'sack',
    ]);

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('viewQuotation', $this->quotation->id)
        ->assertSee('Quotation Detail')
        ->assertSee('Halal Coconut Flour 25kg')
        ->assertSee('sack');
});

test('the detail modal shows award and reject actions for a submitted quotation on an open rfq', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('viewQuotation', $this->quotation->id)
        ->assertSee('Award')
        ->assertSee('Reject');
});

test('confirming award from the modal closes the detail modal', function () {
    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('viewQuotation', $this->quotation->id)
        ->assertSet('showQuotationDetail', true)
        ->call('confirmAwardQuotation', $this->quotation->id)
        ->assertSet('confirmingAwardQuotationId', $this->quotation->id)
        ->assertSet('showQuotationDetail', false);
});
