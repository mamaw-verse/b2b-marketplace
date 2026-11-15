<?php

declare(strict_types=1);

use App\Livewire\Storefront\ProductDetail;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->shop = Shop::factory()->create(['status' => 'active']);
    $plan = SubscriptionPlan::factory()->create();
    Subscription::factory()->active()->create([
        'shop_id' => $this->shop->id,
        'subscription_plan_id' => $plan->id,
    ]);
    $this->product = Product::factory()->create(['shop_id' => $this->shop->id, 'status' => 'published']);
});

test('flagged product returns 404 on storefront', function () {
    $product = Product::factory()->published()->flagged()->create(['shop_id' => $this->shop->id]);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertStatus(404);
});

test('admin_unpublished product returns 404 on storefront', function () {
    $product = Product::factory()->published()->adminUnpublished()->create(['shop_id' => $this->shop->id]);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertStatus(404);
});

test('active moderation_status product is accessible on storefront', function () {
    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertStatus(200);
});

test('clean enabled product video is the first storefront gallery entry', function () {
    Config::set('marketplace.features.product_video_enabled', true);
    $media = Media::factory()->create([
        'uploaded_by_id' => $this->shop->user_id,
        'original_filename' => 'product.mp4',
        'extension' => 'mp4',
        'mime_type' => 'video/mp4',
        'storage_path' => 'media/product.mp4',
        'scan_status' => 'clean',
    ]);
    $this->product->update([
        'video_media_id' => $media->id,
        'featured_image_path' => 'products/test.jpg',
    ]);

    $this->get(route('storefront.product', $this->product))
        ->assertOk()
        ->assertSee('size-20 shrink-0', false)
        ->assertSee('Product video', false);
});

test('flagged product returns 404 on public api', function () {
    $product = Product::factory()->published()->flagged()->create(['shop_id' => $this->shop->id]);

    $this->getJson('/api/v1/products/'.$product->id)
        ->assertNotFound();
});

test('admin_unpublished product returns 404 on public api', function () {
    $product = Product::factory()->published()->adminUnpublished()->create(['shop_id' => $this->shop->id]);

    $this->getJson('/api/v1/products/'.$product->id)
        ->assertNotFound();
});

test('active moderation_status product is accessible on public api', function () {
    $this->getJson('/api/v1/products/'.$this->product->id)
        ->assertOk();
});

test('storefront product detail only loads published variants', function () {
    $published = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'PUB-1',
        'status' => 'published',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'ARCH-1',
        'status' => 'archived',
    ]);

    $component = Livewire::test(ProductDetail::class, ['product' => $this->product]);

    $loaded = $component->get('product')->variants;

    expect($loaded)->toHaveCount(1)
        ->and($loaded->first()->id)->toBe($published->id)
        ->and($loaded->pluck('status')->unique()->all())->toBe(['published']);
});

test('archived variant sku does not render on the storefront', function () {
    ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'PUB-VISIBLE',
        'status' => 'published',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'sku' => 'ARCH-HIDDEN',
        'status' => 'archived',
    ]);

    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertDontSee('ARCH-HIDDEN');
});

test('product detail renders sourcing section at bottom', function () {
    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertSee('Looking for Something Else?')
        ->assertSee('Our sourcing team will help you find the right product.')
        ->assertSee('Custom Sourcing');
});

test('product owner does not see the request quote CTA for their own product', function () {
    $owner = $this->shop->user;
    $owner->email_verified_at ??= now();
    $owner->save();

    $this->actingAs($owner)
        ->get(route('storefront.product', $this->product))
        ->assertOk()
        ->assertSee('This is your product')
        ->assertSee('You cannot request a quote for your own products.');
});

test('plain-text description with newlines renders as line breaks on detail page', function () {
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'status' => 'published',
        'description' => "First line.\nSecond line.\r\nThird line.",
    ]);

    $this->get(route('storefront.product', $product))
        ->assertOk()
        ->assertSee('<br />', false);
});

test('html description from wysiwyg is rendered without double-escaping', function () {
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'status' => 'published',
        'description' => '<p>Grass-fed, <strong>certified halal</strong>.</p>',
    ]);

    $this->get(route('storefront.product', $product))
        ->assertOk()
        ->assertSee('<strong>certified halal</strong>', false);
});

test('halal certified pill and popover render when shop has active certificate', function () {
    ShopCertificate::factory()->create([
        'shop_id' => $this->shop->id,
        'status' => 'active',
        'registration_number' => 'MHCB-003123',
    ]);

    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertSee('Halal Certified')
        ->assertSee('click to view certificate details')
        ->assertSee('MHCB-003123');

    $this->get(route('storefront.product', $this->product))
        ->assertOk()
        ->assertSee('click to view certificate details')
        ->assertSee('MHCB-003123');
});

test('halal certified pill does not render when shop has no certificates', function () {
    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertDontSee('Halal Certified')
        ->assertDontSee('click to view certificate details');

    $this->get(route('storefront.product', $this->product))
        ->assertOk()
        ->assertDontSee('click to view certificate details');
});

test('halal certified pill does not render when shop only has inactive certificates', function () {
    ShopCertificate::factory()->expired()->create([
        'shop_id' => $this->shop->id,
        'registration_number' => 'MHCB-EXPIRED',
    ]);

    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertDontSee('Halal Certified')
        ->assertDontSee('click to view certificate details')
        ->assertDontSee('MHCB-EXPIRED');

    $this->get(route('storefront.product', $this->product))
        ->assertOk()
        ->assertDontSee('click to view certificate details')
        ->assertDontSee('MHCB-EXPIRED');
});

test('redundant halal chain of custody trust strip is not rendered', function () {
    ShopCertificate::factory()->create([
        'shop_id' => $this->shop->id,
        'status' => 'active',
    ]);

    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertDontSee('Halal chain of custody verified');

    $this->get(route('storefront.product', $this->product))
        ->assertOk()
        ->assertDontSee('Halal chain of custody verified');
});

test('share buttons render under gallery viewer when product share is enabled', function () {
    Config::set('marketplace.features.product_share_enabled', true);

    Livewire::test(ProductDetail::class, ['product' => $this->product])
        ->assertSee('Share this product')
        ->assertDontSee('Halal chain of custody verified');

    $response = $this->get(route('storefront.product', $this->product));
    $response->assertOk();

    $content = $response->getContent();
    $sharePos = strpos($content, 'aria-label="Share this product"');

    expect($sharePos)->not->toBeFalse();
});
