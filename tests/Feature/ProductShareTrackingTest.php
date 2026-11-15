<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Storefront\RecordProductShareAction;
use App\Livewire\Storefront\ProductDetail;
use App\Models\Product;
use App\Models\ProductShareStat;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Database\Seeders\RolePermissionSeeder;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->shop = Shop::factory()->create(['status' => 'active']);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->active()->create([
        'shop_id' => $this->shop->id,
        'subscription_plan_id' => $plan->id,
    ]);
    $this->product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'status' => 'published',
        'moderation_status' => 'active',
    ]);

    Config::set('marketplace.features.product_share_enabled', true);
    Config::set('marketplace.share.channels', ['copy', 'facebook', 'messenger', 'x', 'native']);
    Config::set('marketplace.share.dedupe_minutes', 10);
    Cache::flush();
    RateLimiter::clear('product-share:127.0.0.1');
});

test('counts a valid channel: row created, share_count = 1', function () {
    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->call('trackShare', 'facebook')
        ->assertOk();

    expect($this->product->fresh()->share_count)->toBe(1);

    $stat = ProductShareStat::where('product_id', $this->product->id)
        ->where('channel', 'facebook')
        ->where('shared_on', today()->toDateString())
        ->first();

    expect($stat)->not->toBeNull()
        ->and($stat->count)->toBe(1);
});

test('repeat within window not counted; counted after time travel', function () {
    $component = Livewire::test(ProductDetail::class, ['product' => $this->product]);

    $component->call('trackShare', 'facebook');
    expect($this->product->fresh()->share_count)->toBe(1);

    // Immediate repeat within dedupe window
    $component->call('trackShare', 'facebook');
    expect($this->product->fresh()->share_count)->toBe(1);

    $stat = ProductShareStat::where('product_id', $this->product->id)->first();
    expect($stat->count)->toBe(1);

    // Advance time past the 10-minute dedupe window
    $this->travel(11)->minutes();

    $component->call('trackShare', 'facebook');
    expect($this->product->fresh()->share_count)->toBe(2);

    $stat->refresh();
    expect($stat->count)->toBe(2);
});

test('two different channels both count', function () {
    $component = Livewire::test(ProductDetail::class, ['product' => $this->product]);

    $component->call('trackShare', 'facebook');
    $component->call('trackShare', 'x');

    expect($this->product->fresh()->share_count)->toBe(2);

    $facebookStat = ProductShareStat::where('product_id', $this->product->id)->where('channel', 'facebook')->first();
    $xStat = ProductShareStat::where('product_id', $this->product->id)->where('channel', 'x')->first();

    expect($facebookStat->count)->toBe(1)
        ->and($xStat->count)->toBe(1);
});

test('unknown channel ignored; flag off ignored', function () {
    $component = Livewire::test(ProductDetail::class, ['product' => $this->product]);

    // Unknown channel
    $component->call('trackShare', 'myspace');
    expect($this->product->fresh()->share_count)->toBe(0)
        ->and(ProductShareStat::count())->toBe(0);

    // Feature flag off
    Config::set('marketplace.features.product_share_enabled', false);
    $component->call('trackShare', 'facebook');

    expect($this->product->fresh()->share_count)->toBe(0)
        ->and(ProductShareStat::count())->toBe(0);
});

test('rate limit: the 31st call within a minute is dropped', function () {
    $component = Livewire::test(ProductDetail::class, ['product' => $this->product]);

    // Call 30 times
    for ($i = 0; $i < 30; $i++) {
        $component->call('trackShare', 'copy');
    }

    expect(RateLimiter::tooManyAttempts('product-share:127.0.0.1', 30))->toBeTrue();

    // 31st call is dropped before action is invoked
    $mockAction = $this->mock(RecordProductShareAction::class);
    $mockAction->shouldNotReceive('execute');

    $component->call('trackShare', 'copy');
});

test('unpublished / hidden product page returns 404 so nothing can be tracked', function () {
    $archivedProduct = Product::factory()->archived()->create([
        'shop_id' => $this->shop->id,
    ]);

    Livewire::test(ProductDetail::class, ['product' => $archivedProduct])
        ->assertStatus(404);
});

test('no IP or user id is persisted in product_share_stats table', function () {
    $columns = Schema::getColumnListing('product_share_stats');

    expect($columns)->not->toContain('ip')
        ->and($columns)->not->toContain('ip_address')
        ->and($columns)->not->toContain('user_id');
});

test('tracking failure does not surface an exception to the client', function () {
    $mockAction = $this->mock(RecordProductShareAction::class);
    $mockAction->shouldReceive('execute')->andThrow(new Exception('Database unreachable'));

    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->call('trackShare', 'facebook')
        ->assertOk();
});
