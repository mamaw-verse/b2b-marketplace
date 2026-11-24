<?php

declare(strict_types=1);

namespace App\Actions\Seo\Schema;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Schema\PlainText;
use App\Support\Schema\SchemaGraph;
use App\Support\Schema\SchemaId;
use App\Support\Seo\ProductCategoryTrail;
use App\ValueObjects\PublicCompany;

/**
 * B2B product markup. Every value mirrors what the product page shows, and
 * nothing the page does not show is invented:
 *
 * - `Offer` only for variants with a displayed price, in the variant's own
 *   currency, with the MOQ and unit as `eligibleQuantity` and any displayed
 *   volume tiers as `priceSpecification`.
 * - No shipping, returns, `priceValidUntil` or `availability` (the page shows
 *   none of them; decision D2).
 * - No manufacturer (no field records one) and no product-level halal
 *   certification (only the supplier holds certificates).
 * - Images are the featured image only: gallery files are served from `/media`,
 *   which robots.txt disallows.
 */
final class BuildProductNode
{
    private const VARIES_BY = [
        'color' => 'https://schema.org/color',
        'colour' => 'https://schema.org/color',
        'size' => 'https://schema.org/size',
        'material' => 'https://schema.org/material',
        'pattern' => 'https://schema.org/pattern',
    ];

    /**
     * @return array<string, mixed>
     */
    public function handle(Product $product, PublicCompany $supplier): array
    {
        $product->loadMissing(['variants' => fn ($query) => $query->published(), 'categories.parent']);

        $url = route('storefront.product', $product);
        $id = SchemaId::product($url);
        $supplierId = SchemaId::company($supplier->url);

        $variants = $product->variants
            ->filter(static fn (ProductVariant $variant): bool => $variant->status === 'published')
            ->values();

        $common = [
            '@id' => $id,
            'url' => $url,
            'name' => $product->name,
            'description' => PlainText::from($product->description),
            'image' => $this->images($product),
            'brand' => SchemaGraph::ref($supplierId),
            'category' => $this->category($product),
            'aggregateRating' => $this->rating($product),
        ];

        $halal = $this->halalProperty($supplier);

        if ($variants->count() > 1) {
            return ['@type' => 'ProductGroup', ...$common] + [
                'productGroupID' => (string) ($product->slug ?? $product->getKey()),
                'variesBy' => $this->variesBy($variants->all()),
                'hasVariant' => $variants
                    ->map(fn (ProductVariant $variant): array => $this->variant($product, $variant, $common, $id, $url, $supplierId))
                    ->all(),
                'additionalProperty' => [$halal],
            ];
        }

        $variant = $variants->first();

        return ['@type' => 'Product', ...$common] + [
            'sku' => $variant?->sku,
            'additionalProperty' => [...($variant ? $this->attributeProperties($variant) : []), $halal],
            'offers' => $variant ? $this->offer($variant, $url, $supplierId) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $common
     * @return array<string, mixed>
     */
    private function variant(Product $product, ProductVariant $variant, array $common, string $groupId, string $url, string $supplierId): array
    {
        return [
            '@type' => 'Product',
            '@id' => SchemaId::variant($url, (string) $variant->sku),
            'name' => $product->name,
            'sku' => $variant->sku,
            'description' => PlainText::from($variant->description),
            'image' => $common['image'],
            'brand' => $common['brand'],
            'inProductGroupWithID' => (string) ($product->slug ?? $product->getKey()),
            'additionalProperty' => $this->attributeProperties($variant),
            'offers' => $this->offer($variant, $url, $supplierId),
        ];
    }

    /**
     * @return list<string>|null
     */
    private function images(Product $product): ?array
    {
        $url = $product->featured_image_url;

        return is_string($url) && $url !== '' ? [$url] : null;
    }

    private function category(Product $product): ?string
    {
        $names = ProductCategoryTrail::names($product);

        return $names === [] ? null : implode(' > ', $names);
    }

    /**
     * Only with reviews switched on, at least one review and a rating to show.
     *
     * @return array<string, mixed>|null
     */
    private function rating(Product $product): ?array
    {
        $rating = (float) $product->average_rating;
        $count = (int) $product->total_reviews;

        if (! config('marketplace.features.reviews_enabled') || $count < 1 || $rating <= 0) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => round($rating, 1),
            'reviewCount' => $count,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function halalProperty(PublicCompany $supplier): ?array
    {
        if (! $supplier->isHalalCertified()) {
            return null;
        }

        return [
            '@type' => 'PropertyValue',
            'name' => 'Supplier halal certification',
            'value' => 'Active',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attributeProperties(ProductVariant $variant): array
    {
        $properties = [];

        foreach ((array) ($variant->attributes ?? []) as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $properties[] = [
                '@type' => 'PropertyValue',
                'name' => $this->attributeLabel((string) $key),
                'value' => (string) $value,
            ];
        }

        return $properties;
    }

    /**
     * The attributes whose value actually differs between the published variants.
     *
     * @param  list<ProductVariant>  $variants
     * @return list<string>
     */
    private function variesBy(array $variants): array
    {
        $values = [];

        foreach ($variants as $variant) {
            foreach ((array) ($variant->attributes ?? []) as $key => $value) {
                if (is_scalar($value)) {
                    $values[(string) $key][(string) $value] = true;
                }
            }
        }

        $varies = [];

        foreach ($values as $key => $distinct) {
            if (count($distinct) < 2) {
                continue;
            }

            $normalised = mb_strtolower(preg_replace('/^(attr|attribute)_/i', '', $key) ?? $key);
            $varies[] = self::VARIES_BY[$normalised] ?? $this->attributeLabel($key);
        }

        return array_values(array_unique($varies));
    }

    /**
     * Same humanising the product page applies to attribute names.
     */
    private function attributeLabel(string $key): string
    {
        return ucfirst(str_replace('_', ' ', preg_replace('/^(attr|attribute)_/i', '', $key) ?? $key));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function offer(ProductVariant $variant, string $productUrl, string $supplierId): ?array
    {
        $price = (float) $variant->price;
        $currency = mb_strtoupper(trim((string) $variant->currency));

        if ($price <= 0 || preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            return null;
        }

        $unit = PlainText::from($variant->unit);
        $moq = max(1, (int) $variant->minimum_order_quantity);

        $specification = fn (float $amount, int $minimum): array => [
            '@type' => 'UnitPriceSpecification',
            'price' => $this->money($amount),
            'priceCurrency' => $currency,
            'eligibleQuantity' => $this->quantity($minimum, $unit),
        ];

        $tiers = [];

        foreach ((array) ($variant->quantity_breaks ?? []) as $break) {
            $minimum = (int) ($break['min_qty'] ?? 0);
            $amount = (float) ($break['price'] ?? $price);

            if ($minimum > 0 && $amount > 0) {
                $tiers[$minimum] = $specification($amount, $minimum);
            }
        }

        ksort($tiers);

        return [
            '@type' => 'Offer',
            'url' => $productUrl,
            'price' => $this->money($price),
            'priceCurrency' => $currency,
            'eligibleQuantity' => $this->quantity($moq, $unit),
            'priceSpecification' => $tiers === [] ? null : [$specification($price, $moq), ...array_values($tiers)],
            'seller' => SchemaGraph::ref($supplierId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quantity(int $minimum, ?string $unit): array
    {
        return ['@type' => 'QuantitativeValue', 'minValue' => $minimum, 'unitText' => $unit];
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
