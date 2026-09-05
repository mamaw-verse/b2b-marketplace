<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Disable all phase 2-4 flags
    Config::set('marketplace.features.payouts_enabled', false);
    Config::set('marketplace.features.orders_enabled', false);
    Config::set('marketplace.features.escrow_payments_enabled', false);
    Config::set('marketplace.features.fulfilment_enabled', false);
    Config::set('marketplace.features.disputes_enabled', false);
    Config::set('marketplace.features.reviews_enabled', false);
    Config::set('marketplace.features.quotations_enabled', false);
    Config::set('marketplace.features.seller_rfq_queue_enabled', false);
    Config::set('marketplace.features.chat_enabled', false);
});

test('disabled routes return 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/account/orders')
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/hub/payouts')
        ->assertNotFound();
});

test('chat routes return 404 when disabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/account/messages')
        ->assertNotFound();
});
