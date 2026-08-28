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
