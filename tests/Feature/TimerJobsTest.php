<?php

declare(strict_types=1);

use App\Jobs\QuotationExpiryJob;
use App\Jobs\RfqAutoCloseJob;
use App\Jobs\SellerNoBidTimeoutJob;
use App\Models\BuyerProfile;
use App\Models\Quotation;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('SellerNoBidTimeoutJob', function () {
    test('transitions inactive recipients to no_bid', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $buyer->id]);
        $shop = Shop::factory()->create();
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
        $recipient = RfqRecipient::factory()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
            'status' => 'received',
            'last_activity_at' => now()->subDays(8),
        ]);

        (new SellerNoBidTimeoutJob)->handle();

        $recipient->refresh();
        expect($recipient->status)->toBe('no_bid');

        Notification::assertSentTo($buyer, App\Notifications\RfqNoBidNotification::class);
    });

    test('does not transition already no_bid recipients', function () {
        Notification::fake();

        $shop = Shop::factory()->create();
        $rfq = Rfq::factory()->create();
        RfqRecipient::factory()->noBid()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
            'last_activity_at' => now()->subDays(8),
        ]);

        (new SellerNoBidTimeoutJob)->handle();

        Notification::assertNothingSent();
    });

    test('does not transition recipients with recent activity', function () {
        Notification::fake();

        $shop = Shop::factory()->create();
        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
            'status' => 'reviewed',
            'last_activity_at' => now()->subDays(3),
        ]);

        (new SellerNoBidTimeoutJob)->handle();

        $recipient->refresh();
        expect($recipient->status)->toBe('reviewed');
        Notification::assertNothingSent();
    });

    test('is idempotent - repeated runs do not duplicate', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $buyer->id]);
        $shop = Shop::factory()->create();
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
        RfqRecipient::factory()->create([
            'rfq_id' => $rfq->id,
            'shop_id' => $shop->id,
            'status' => 'received',
            'last_activity_at' => now()->subDays(8),
        ]);

        (new SellerNoBidTimeoutJob)->handle();
        (new SellerNoBidTimeoutJob)->handle();
        (new SellerNoBidTimeoutJob)->handle();

        Notification::assertSentToTimes($buyer, App\Notifications\RfqNoBidNotification::class, 1);
    });
});

describe('RfqAutoCloseJob', function () {
    test('closes inactive open RFQs and cascades quotations', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create([
            'user_id' => $buyer->id,
            'status' => 'open',
            'last_activity_at' => now()->subDays(8),
        ]);
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
        $submittedQuotation = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);
        $awardedQuotation = Quotation::factory()->awarded()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        (new RfqAutoCloseJob)->handle();

        $rfq->refresh();
        expect($rfq->status)->toBe('closed');
        expect($rfq->closed_at)->not->toBeNull();

        $submittedQuotation->refresh();
        expect($submittedQuotation->status)->toBe('not_awarded');

        // Already-terminal quotation unchanged
        $awardedQuotation->refresh();
        expect($awardedQuotation->status)->toBe('awarded');

        Notification::assertSentTo($seller, App\Notifications\RfqClosedNotification::class);
    });

    test('does not close RFQs with recent activity', function () {
        Notification::fake();

        $rfq = Rfq::factory()->create([
            'status' => 'open',
            'last_activity_at' => now()->subDays(3),
        ]);

        (new RfqAutoCloseJob)->handle();

        $rfq->refresh();
        expect($rfq->status)->toBe('open');
        Notification::assertNothingSent();
    });

    test('is idempotent - repeated runs do not duplicate', function () {
        Notification::fake();

        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create([
            'status' => 'open',
            'last_activity_at' => now()->subDays(8),
        ]);
        RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);

        (new RfqAutoCloseJob)->handle();
        (new RfqAutoCloseJob)->handle();
        (new RfqAutoCloseJob)->handle();

        Notification::assertSentToTimes($seller, App\Notifications\RfqClosedNotification::class, 1);
    });
});

describe('QuotationExpiryJob', function () {
    test('expires submitted quotations past valid_until', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
        $quotation = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
            'valid_until' => now()->subDay(),
        ]);

        (new QuotationExpiryJob)->handle();

        $quotation->refresh();
        expect($quotation->status)->toBe('expired');

        Notification::assertSentTo($buyer, App\Notifications\QuotationExpiredNotification::class);
        Notification::assertSentTo($seller, App\Notifications\QuotationExpiredNotification::class);
    });

    test('does not expire quotations still within valid_until', function () {
        Notification::fake();

        $rfq = Rfq::factory()->create();
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id]);
        $quotation = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
            'valid_until' => now()->addDays(5),
        ]);

        (new QuotationExpiryJob)->handle();

        $quotation->refresh();
        expect($quotation->status)->toBe('submitted');
        Notification::assertNothingSent();
    });

    test('is idempotent - repeated runs do not duplicate', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
        Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
            'valid_until' => now()->subDay(),
        ]);

        (new QuotationExpiryJob)->handle();
        (new QuotationExpiryJob)->handle();
        (new QuotationExpiryJob)->handle();

        Notification::assertSentToTimes($buyer, App\Notifications\QuotationExpiredNotification::class, 1);
    });
});

describe('Scheduler Registration', function () {
    test('all three Phase 2 timer jobs are scheduled', function () {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('SellerNoBidTimeoutJob')
            ->expectsOutputToContain('RfqAutoCloseJob')
            ->expectsOutputToContain('QuotationExpiryJob');
    });
});
