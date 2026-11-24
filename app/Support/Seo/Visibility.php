<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;

/**
 * The single definition of "publicly visible" for sitemaps, canonical slug
 * redirects and (later) llms.txt. Mirrors what the storefront itself shows:
 * active shops, gated by subscription when subscriptions are enabled, and
 * `Product::marketplaceVisible()` for products.
 */
final class Visibility
{
    /**
     * @return Builder<Shop>
     */
    public static function shops(): Builder
    {
        $query = Shop::query()->where('shops.status', 'active');

        if (config('marketplace.features.subscriptions_enabled')) {
            $query->whereHas('subscriptions', fn (Builder $subscriptions) => $subscriptions->where('status', 'active'));
        }

        return $query;
    }

    public static function isShopVisible(Shop $shop): bool
    {
        $visible = $shop->status === 'active';

        if ($visible && config('marketplace.features.subscriptions_enabled')) {
            $visible = $shop->hasActiveSubscription();
        }

        return $visible;
    }

    /**
     * @return Builder<Product>
     */
    public static function products(): Builder
    {
        return Product::marketplaceVisible();
    }

    public static function isProductVisible(Product $product): bool
    {
        return self::products()->whereKey($product->getKey())->exists();
    }
}
