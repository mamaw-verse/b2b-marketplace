<?php

declare(strict_types=1);

use App\Support\Seo\TrustFacts;
use Illuminate\Support\Facades\Config;

/** Feature flag (config path) => the fact key it gates and a phrase that fact must contain. */
dataset('gated trust facts', [
    'quotations' => ['marketplace.features.quotations_enabled', 'quotations', 'quotations'],
    'chat' => ['marketplace.features.chat_enabled', 'chat', 'message each other'],
    'orders' => ['marketplace.features.orders_enabled', 'orders', 'place and track orders'],
    'escrow' => ['marketplace.features.escrow_payments_enabled', 'escrow', 'escrow'],
    'disputes' => ['marketplace.features.disputes_enabled', 'disputes', 'dispute'],
    'reviews' => ['marketplace.features.reviews_enabled', 'reviews', 'ratings and reviews'],
    'antivirus' => ['services.clamav.enabled', 'document_scanning', 'scanned for malware'],
]);

it('states a gated claim only while its flag is on', function (string $flag, string $key, string $phrase) {
    Config::set($flag, false);
    $off = app(TrustFacts::class);

    expect($off->has($key))->toBeFalse()
        ->and(implode(' ', $off->sentences()))->not->toContain($phrase);

    Config::set($flag, true);
    $on = app(TrustFacts::class);

    expect($on->has($key))->toBeTrue()
        ->and(implode(' ', $on->sentences()))->toContain($phrase);
})->with('gated trust facts');

/** @return list<string> every flag that gates a fact */
function trustFactFlags(): array
{
    return [
        'marketplace.features.quotations_enabled',
        'marketplace.features.chat_enabled',
        'marketplace.features.orders_enabled',
        'marketplace.features.escrow_payments_enabled',
        'marketplace.features.disputes_enabled',
        'marketplace.features.reviews_enabled',
        'services.clamav.enabled',
    ];
}

it('with every optional capability off only states the always-true facts', function () {
    foreach (trustFactFlags() as $flag) {
        Config::set($flag, false);
    }

    expect(array_keys(app(TrustFacts::class)->all()))->toBe(['onboarding_approval', 'certificate_tracking']);
});

it('takes the certificate alert days from the scheduled job', function () {
    expect(app(TrustFacts::class)->all()['certificate_tracking'])->toContain('60, 30 and 7 days');
});

it('never states a commission rate or a fee model', function () {
    foreach (trustFactFlags() as $flag) {
        Config::set($flag, true);
    }

    $text = strtolower(implode(' ', app(TrustFacts::class)->sentences()));

    expect($text)->not->toContain('commission')->not->toContain('%')->not->toContain('monthly')->not->toContain('free');
});
