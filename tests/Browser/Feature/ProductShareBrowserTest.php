<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

beforeEach(function (): void {
    config()->set('marketplace.features.product_share_enabled', true);
    config()->set('marketplace.features.subscriptions_enabled', false);
    config()->set('humancheck.enabled', false);
});

function shareBrowserFixture(): array
{
    $owner = User::factory()->create();
    $shop = Shop::factory()->create([
        'user_id' => $owner->id,
        'status' => 'active',
        'storefront_name' => 'Barakah Organic',
        'slug' => 'barakah-organic',
    ]);
    $product = Product::factory()->published()->create([
        'shop_id' => $shop->id,
        'name' => 'Matcha Green Tea',
        'slug' => 'matcha-green-tea',
    ]);

    return [$owner, $shop, $product];
}

test('Facebook and X links have target=_blank and rel containing noopener', function (): void {
    [, , $product] = shareBrowserFixture();

    $page = visit('/products/'.$product->slug)
        ->assertSee('Matcha Green Tea');

    $fbTarget = $page->attribute('a[aria-label="Share on Facebook"]', 'target');
    $fbRel = $page->attribute('a[aria-label="Share on Facebook"]', 'rel');
    $xTarget = $page->attribute('a[aria-label="Share on X"]', 'target');
    $xRel = $page->attribute('a[aria-label="Share on X"]', 'rel');

    expect($fbTarget)->toBe('_blank')
        ->and($fbRel)->toContain('noopener')
        ->and($xTarget)->toBe('_blank')
        ->and($xRel)->toContain('noopener');
});

test('click Copy link displays Link copied and increments share count', function (): void {
    [, , $product] = shareBrowserFixture();

    $page = visit('/products/'.$product->slug)
        ->assertSee('Matcha Green Tea')
        ->click('button[aria-label="Copy link"]');

    $page->assertSee('Link copied');

    // Give the unawaited Livewire request a moment to execute
    $shareCount = 0;
    $deadline = microtime(true) + 10;
    while (microtime(true) < $deadline) {
        $shareCount = (int) $product->fresh()->share_count;
        if ($shareCount === 1) {
            break;
        }
        usleep(100_000);
    }

    expect($shareCount)->toBe(1);
});

test('with navigator.share stubbed, Instagram button appears and calls it; absent when removed', function (): void {
    [, , $product] = shareBrowserFixture();

    // Absent when navigator.share is undefined
    $page = visit('/products/'.$product->slug)
        ->assertSee('Matcha Green Tea');

    $page->assertMissing('button[aria-label="Share on Instagram"]');

    // Stub navigator.share and flip the Alpine canShare flag, which is
    // computed once in init() before the stub can be injected.
    $page->script('(() => {
        window.navigator.share = function (data) {
            window.__sharedData = data;
            return Promise.resolve();
        };
        const group = document.querySelector(\'[role="group"][aria-label="Share this product"]\');
        if (group) {
            if (window.Alpine && Alpine.$data) {
                Alpine.$data(group).canShare = true;
            } else if (group._x_dataStack) {
                group._x_dataStack.forEach((d) => { d.canShare = true; });
            }
        }
    })()');

    $page->assertVisible('button[aria-label="Share on Instagram"]')
        ->click('button[aria-label="Share on Instagram"]');

    $sharedUrl = null;
    $deadline = microtime(true) + 10;
    while (microtime(true) < $deadline) {
        $sharedUrl = $page->script('window.__sharedData ? window.__sharedData.url : null');
        if (is_string($sharedUrl)) {
            break;
        }
        usleep(100_000);
    }

    expect($sharedUrl)->toContain('/products/'.$product->slug);
});
