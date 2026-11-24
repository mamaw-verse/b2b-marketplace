<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Actions\Seo\Schema\BuildProductNode;
use App\Actions\Seo\Schema\BuildSupplierNode;
use App\Models\Product;
use App\Support\Schema\SchemaId;
use App\Support\Seo\ProductCategoryTrail;
use App\Support\Seo\SeoData;
use App\Support\Seo\Visibility;
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

        $image = $this->socialImage($product);

        return new SeoData(
            title: $this->title($product),
            description: $description !== '' ? $description : null,
            canonical: route('storefront.product', $product),
            ogType: 'product',
            image: $image,
            imageAlt: $image !== null ? $product->name : null,
            breadcrumbs: $this->breadcrumbs($product),
            schemaNodes: $nodes,
            mainEntityId: $mainEntityId,
        );
    }

    /**
     * The product's own photo for social previews and `primaryImageOfPage`: the
     * versioned featured-image URL, and only while the product is publicly
     * visible. Null means "use the default social image".
     */
    private function socialImage(Product $product): ?string
    {
        if (! $product->featured_image_path || ! Visibility::isProductVisible($product)) {
            return null;
        }

        return $product->featured_image_url;
    }

    public function title(Product $product): string
    {
        return $product->name.($product->shop ? ' — '.$product->shop->storefront_name : '');
    }

    /**
     * Mirrors the breadcrumb the product page shows: Products › parent group ›
     * primary category › product, each category linking to its landing page.
     *
     * @return list<array{name: string, url: string}>
     */
    public function breadcrumbs(Product $product): array
    {
        $trail = [['name' => __('Products'), 'url' => route('storefront.products')]];

        $category = ProductCategoryTrail::primary($product);

        if ($category !== null) {
            if ($category->parent !== null) {
                $trail[] = ['name' => $category->parent->name, 'url' => route('storefront.category', $category->parent)];
            }

            $trail[] = ['name' => $category->name, 'url' => route('storefront.category', $category)];
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
