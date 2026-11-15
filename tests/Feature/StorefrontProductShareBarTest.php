<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->shop = Shop::factory()->create(['status' => 'active']);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->active()->create([
        'shop_id' => $this->shop->id,
        'subscription_plan_id' => $plan->id,
    ]);
    $this->product = Product::factory()->published()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Organic Matcha Powder',
        'slug' => 'organic-matcha-powder',
    ]);
    Config::set('marketplace.features.product_share_enabled', true);
    Config::set('marketplace.share.public_min', 5);
});

test('bar renders with canonical slug URL in the Facebook and X hrefs, and URL-encoded', function () {
    $canonicalUrl = route('storefront.product', $this->product);
    $encodedUrl = urlencode($canonicalUrl);
    $encodedText = urlencode($this->product->name);

    $response = $this->get($canonicalUrl);

    $response->assertOk();
    $response->assertSee('https://www.facebook.com/sharer/sharer.php?u='.$encodedUrl, false);
    $response->assertSee('https://x.com/intent/post?url='.$encodedUrl, false);
    $response->assertSee('text='.$encodedText, false);
    $response->assertSee('target="_blank"', false);
    $response->assertSee('rel="noopener noreferrer"', false);
});

test('Messenger link uses fb-messenger://share/?link=', function () {
    $canonicalUrl = route('storefront.product', $this->product);
    $encodedUrl = urlencode($canonicalUrl);

    $response = $this->get($canonicalUrl);

    $response->assertOk();
    $response->assertSee('fb-messenger://share/?link='.$encodedUrl, false);
});

test('share bar is hidden when flag is off', function () {
    Config::set('marketplace.features.product_share_enabled', false);

    $canonicalUrl = route('storefront.product', $this->product);
    $encodedUrl = urlencode($canonicalUrl);

    $response = $this->get($canonicalUrl);

    $response->assertOk();
    $response->assertDontSee('https://www.facebook.com/sharer/sharer.php?u='.$encodedUrl, false);
    $response->assertDontSee('fb-messenger://share/?link='.$encodedUrl, false);
    $response->assertDontSee('Share this product');
});

test('public count is hidden below public_min, shown at and above it', function () {
    $canonicalUrl = route('storefront.product', $this->product);

    // Below threshold (share_count = 4, public_min = 5)
    $this->product->forceFill(['share_count' => 4])->save();
    $response = $this->get($canonicalUrl);
    $response->assertOk();
    $response->assertDontSee('4 shares');

    // At threshold (share_count = 5)
    $this->product->forceFill(['share_count' => 5])->save();
    $response = $this->get($canonicalUrl);
    $response->assertOk();
    $response->assertSee('5 shares');

    // Above threshold (share_count = 12)
    $this->product->forceFill(['share_count' => 12])->save();
    $response = $this->get($canonicalUrl);
    $response->assertOk();
    $response->assertSee('12 shares');
});
