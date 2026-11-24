<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Actions\Seo\Schema\BuildProductNode;
use App\Actions\Seo\Schema\BuildSupplierNode;
use App\Models\Product;
use App\Support\Schema\SchemaId;
use App\Support\Seo\ProductCategoryTrail;
use App\Support\Seo\SeoData;
use App\ValueObjects\PublicCompany;
use Illuminate\Support\Str;

final class BuildProductSeoData
{
    public function __construct(
        private readonly BuildProductNode $productNode,
        private readonly BuildSupplierNode $supplierNode,
    ) {}

    public function handle(Product $product): SeoData
    {
        $description = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $product->description)) ?? ''), 160);

        $nodes = [];
        $mainEntityId = null;

        if ($product->shop !== null) {
            $supplier = PublicCompany::fromShop($product->shop);
            $productNode = $this->productNode->handle($product, $supplier);

            // The supplier appears as an identifying stub; its full profile lives on its own page.
            $nodes = [$this->supplierNode->handle($supplier, full: false), $productNode];
            $mainEntityId = $productNode['@id'];
        }

        return new SeoData(
            title: $this->title($product),
            description: $description !== '' ? $description : null,
            canonical: route('storefront.product', $product),
            ogType: 'product',
            image: $product->featured_image_path ? route('products.featured-image', $product) : null,
            imageAlt: $product->name,
            breadcrumbs: $this->breadcrumbs($product),
            schemaNodes: $nodes,
            mainEntityId: $mainEntityId,
        );
    }

    public function title(Product $product): string
    {
        return $product->name.($product->shop ? ' — '.$product->shop->storefront_name : '');
    }

    /**
     * Mirrors what the product page links: Products › primary category › product.
     *
     * @return list<array{name: string, url: string}>
     */
    public function breadcrumbs(Product $product): array
    {
        $trail = [['name' => __('Products'), 'url' => route('storefront.products')]];

        $category = ProductCategoryTrail::primary($product);

        if ($category !== null) {
            $trail[] = ['name' => $category->name, 'url' => route('storefront.products', ['category' => $category->id])];
        }

        $trail[] = ['name' => $product->name, 'url' => route('storefront.product', $product)];

        return $trail;
    }

    /**
     * Stub for a listing page's ItemList; shares the product page's `@id`.
     *
     * @return array<string, mixed>
     */
    public function listItem(Product $product): array
    {
        $url = route('storefront.product', $product);

        return [
            '@type' => 'Product',
            '@id' => SchemaId::product($url),
            'url' => $url,
            'name' => $product->name,
            'image' => $product->featured_image_url,
        ];
    }
}
