<?php

declare(strict_types=1);

use App\Models\PayoutSetting;
use App\Models\SubscriptionPlan;
use App\Support\Seo\FaqEntries;
use App\Support\Seo\TrustFacts;
use Database\Seeders\SubscriptionPlanSeeder;
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

function setCommissionRate(string $rate): void
{
    PayoutSetting::query()->updateOrCreate(['key' => 'commission_fee_rate'], ['value' => $rate]);
}

function yearlyPlan(array $overrides = []): SubscriptionPlan
{
    return SubscriptionPlan::factory()->create(array_merge([
        'name' => 'Annual Membership Plan',
        'price' => '600.00',
        'currency' => 'PHP',
        'billing_period' => 'yearly',
        'trial_days' => 180,
        'is_active' => true,
    ], $overrides));
}

it('states the commission from the payout setting, and only while orders are on', function () {
    Config::set('marketplace.features.orders_enabled', true);
    setCommissionRate('0.10');

    expect(app(TrustFacts::class)->all()['commission'])->toBe('Suppliers pay a 10% commission on completed sales.');

    setCommissionRate('0.025');

    expect(app(TrustFacts::class)->all()['commission'])->toContain('2.5%');

    Config::set('marketplace.features.orders_enabled', false);

    expect(app(TrustFacts::class)->has('commission'))->toBeFalse()
        ->and(strtolower(implode(' ', app(TrustFacts::class)->sentences())))->not->toContain('commission');
});

it('falls back to the configured rate (10%) when no payout setting exists', function () {
    Config::set('marketplace.features.orders_enabled', true);
    PayoutSetting::query()->where('key', 'commission_fee_rate')->delete();

    expect(PayoutSetting::getCommissionFeeRate())->toBe(0.10)
        ->and(app(TrustFacts::class)->commissionPercent())->toBe('10');
});

it('states the annual membership and the six-month waiver from the active plan', function () {
    Config::set('marketplace.features.subscriptions_enabled', true);
    yearlyPlan();

    expect(app(TrustFacts::class)->all()['membership'])
        ->toBe('Suppliers pay an annual membership of PHP 600 per year, and the first 6 months are free.');
});

it('does not claim a free period when the plan has none', function () {
    Config::set('marketplace.features.subscriptions_enabled', true);
    yearlyPlan(['trial_days' => null]);

    expect(app(TrustFacts::class)->all()['membership'])
        ->toBe('Suppliers pay an annual membership of PHP 600 per year.');
});

it('states no membership while subscriptions are off or no active yearly plan exists', function () {
    yearlyPlan();
    Config::set('marketplace.features.subscriptions_enabled', false);

    expect(app(TrustFacts::class)->has('membership'))->toBeFalse();

    Config::set('marketplace.features.subscriptions_enabled', true);
    SubscriptionPlan::query()->update(['is_active' => false]);

    expect(app(TrustFacts::class)->has('membership'))->toBeFalse();
});

it('answers the cost question from the fee facts and hides it when none apply', function () {
    Config::set('marketplace.features.orders_enabled', true);
    Config::set('marketplace.features.subscriptions_enabled', true);
    setCommissionRate('0.10');
    yearlyPlan();

    $answers = collect(app(FaqEntries::class)->flat())->firstWhere('id', 'faq-42');

    expect($answers['a'])->toContain('PHP 600')->toContain('10% commission');

    Config::set('marketplace.features.orders_enabled', false);
    Config::set('marketplace.features.subscriptions_enabled', false);

    expect(collect(app(FaqEntries::class)->flat())->firstWhere('id', 'faq-42'))->toBeNull();
});

it('waives the first six months only on the Philippines instance', function () {
    Config::set('marketplace.instance_country', 'PH');
    (new SubscriptionPlanSeeder)->run();

    expect(SubscriptionPlan::query()->where('name', 'Annual Membership Plan')->value('trial_days'))->toBe(180);

    Config::set('marketplace.instance_country', 'ID');
    (new SubscriptionPlanSeeder)->run();

    expect(SubscriptionPlan::query()->where('name', 'Annual Membership Plan')->value('trial_days'))->toBeNull();
});
