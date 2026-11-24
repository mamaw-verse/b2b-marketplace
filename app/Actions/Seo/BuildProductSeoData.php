<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Models\Product;
use App\Support\Seo\SeoData;
use Illuminate\Support\Str;

final class BuildProductSeoData
{
    public function handle(Product $product): SeoData
    {
        $description = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $product->description)) ?? ''), 160);

        return new SeoData(
            title: $this->title($product),
            description: $description !== '' ? $description : null,
            canonical: route('storefront.product', $product),
            ogType: 'product',
            image: $product->featured_image_path ? route('products.featured-image', $product) : null,
            imageAlt: $product->name,
        );
    }

    public function title(Product $product): string
    {
        return $product->name.($product->shop ? ' — '.$product->shop->storefront_name : '');
    }
}
