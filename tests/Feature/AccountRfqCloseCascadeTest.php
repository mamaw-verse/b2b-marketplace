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

test('closing an RFQ cascades non-terminal recipients to closed', function () {
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

test('closing an RFQ does not touch no_bid recipients', function () {
    $noBid = makeRecipient($this->rfq, 'no_bid');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('closeRfq');

    $this->assertDatabaseHas('rfq_recipients', [
        'id' => $noBid->id,
        'status' => 'no_bid',
    ]);
});

test('closing an RFQ does not close recipients whose quotation was awarded', function () {
    // The recipient stays in_progress; a recipient with an awarded quotation is
    // excluded from the cascade so its awarded relationship is preserved.
    $awardedRecipient = makeRecipient($this->rfq, 'in_progress');
    Quotation::factory()->awarded()->create([
        'rfq_id' => $this->rfq->id,
        'rfq_recipient_id' => $awardedRecipient->id,
    ]);

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->call('closeRfq');

    $this->assertDatabaseHas('rfq_recipients', [
        'id' => $awardedRecipient->id,
        'status' => 'in_progress',
    ]);

    $this->assertDatabaseHas('quotations', [
        'rfq_recipient_id' => $awardedRecipient->id,
        'status' => 'awarded',
    ]);
});

test('the buyer view shows the seller close notice and reason', function () {
    $recipient = makeRecipient($this->rfq, 'no_bid');
    $recipient->update(['decline_reason' => 'Cannot fulfil this volume within the timeline.']);

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertSee('The seller closed this RFQ.')
        ->assertSee('Cannot fulfil this volume within the timeline.');
});

test('the buyer view shows the close notice without a reason when none was given', function () {
    makeRecipient($this->rfq, 'no_bid');

    Livewire::actingAs($this->buyer)
        ->test(RfqDetail::class, ['rfq' => $this->rfq])
        ->assertSee('The seller closed this RFQ.')
        ->assertDontSee('Reason:');
});
