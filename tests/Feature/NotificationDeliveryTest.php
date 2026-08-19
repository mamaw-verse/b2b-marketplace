<?php

declare(strict_types=1);

use App\Events\QuotationAwarded;
use App\Events\QuotationRejected;
use App\Events\QuotationSubmitted;
use App\Events\RfqClosed;
use App\Events\RfqCreated;
use App\Listeners\NotifyBuyerQuotationSubmitted;
use App\Listeners\NotifySellerQuotationAwarded;
use App\Listeners\NotifySellerQuotationRejected;
use App\Listeners\NotifySellerRfqClosed;
use App\Listeners\NotifySellerRfqReceived;
use App\Models\BuyerProfile;
use App\Models\Quotation;
use App\Models\Rfq;
use App\Models\RfqRecipient;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\QuotationAwardedNotification;
use App\Notifications\QuotationRejectedNotification;
use App\Notifications\QuotationSubmittedNotification;
use App\Notifications\RfqClosedNotification;
use App\Notifications\RfqReceivedNotification;
use Illuminate\Support\Facades\Notification;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Notification Delivery', function () {
    test('RFQ creation notifies seller', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        BuyerProfile::factory()->create(['user_id' => $buyer->id]);
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
        RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);

        (new NotifySellerRfqReceived)->handle(new RfqCreated($rfq));

        Notification::assertSentTo($seller, RfqReceivedNotification::class);
        Notification::assertNotSentTo($buyer, RfqReceivedNotification::class);
    });

    test('quotation submission notifies buyer', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
        $quotation = Quotation::factory()->submitted()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        (new NotifyBuyerQuotationSubmitted)->handle(new QuotationSubmitted($quotation));

        Notification::assertSentTo($buyer, QuotationSubmittedNotification::class);
        Notification::assertNotSentTo($seller, QuotationSubmittedNotification::class);
    });

    test('quotation award notifies seller', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
        $quotation = Quotation::factory()->awarded()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        (new NotifySellerQuotationAwarded)->handle(new QuotationAwarded($quotation));

        Notification::assertSentTo($seller, QuotationAwardedNotification::class);
        Notification::assertNotSentTo($buyer, QuotationAwardedNotification::class);
    });

    test('quotation rejection notifies seller', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->create(['user_id' => $buyer->id]);
        $recipient = RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);
        $quotation = Quotation::factory()->rejected()->create([
            'rfq_id' => $rfq->id,
            'rfq_recipient_id' => $recipient->id,
        ]);

        (new NotifySellerQuotationRejected)->handle(new QuotationRejected($quotation));

        Notification::assertSentTo($seller, QuotationRejectedNotification::class);
        Notification::assertNotSentTo($buyer, QuotationRejectedNotification::class);
    });

    test('RFQ close notifies seller', function () {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $shop = Shop::factory()->create(['user_id' => $seller->id]);
        $rfq = Rfq::factory()->closed()->create(['user_id' => $buyer->id]);
        RfqRecipient::factory()->create(['rfq_id' => $rfq->id, 'shop_id' => $shop->id]);

        (new NotifySellerRfqClosed)->handle(new RfqClosed($rfq));

        Notification::assertSentTo($seller, RfqClosedNotification::class);
        Notification::assertNotSentTo($buyer, RfqClosedNotification::class);
    });
});
