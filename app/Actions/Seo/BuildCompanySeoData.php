<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Actions\Seo\Schema\BuildSupplierNode;
use App\Models\Shop;
use App\Support\Schema\SchemaId;
use App\Support\Seo\SeoData;
use App\ValueObjects\PublicCompany;
use Illuminate\Support\Str;

final class BuildCompanySeoData
{
    public function __construct(private readonly BuildSupplierNode $supplierNode) {}

    /**
     * Reads only the public-data allow-list of Shop (name, description,
     * city/region/country, certificates); never contact, address or
     * registration fields.
     */
    public function handle(Shop $shop): SeoData
    {
        $description = filled($shop->company_description)
            ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $shop->company_description)) ?? ''), 160)
            : __('Discover :name on :app — a verified halal-certified business on the marketplace.', ['name' => $shop->storefront_name, 'app' => config('app.name')]);

        $company = PublicCompany::fromShop($shop);
        $supplier = $this->supplierNode->handle($company, full: true);

        return new SeoData(
            title: $shop->storefront_name.' — '.config('app.name'),
            description: $description,
            canonical: route('storefront.company', $shop),
            // Kept as-is from the previous markup; revisited in Phase 4 (decision D5).
            ogType: 'business.business',
            imageAlt: $shop->storefront_name,
            breadcrumbs: [
                ['name' => __('Home'), 'url' => route('home')],
                ['name' => __('Companies'), 'url' => route('storefront.companies')],
                ['name' => $company->name, 'url' => $company->url],
            ],
            schemaNodes: [$supplier],
            mainEntityId: $supplier['@id'],
        );
    }

    public function title(Shop $shop): string
    {
        return $shop->storefront_name.' — '.config('app.name');
    }

    /**
     * Stub for a listing page's ItemList; shares the company page's `@id`.
     *
     * @return array<string, mixed>
     */
    public function listItem(Shop $shop): array
    {
        $url = route('storefront.company', $shop);

        return [
            '@type' => 'Organization',
            '@id' => SchemaId::company($url),
            'url' => $url,
            'name' => $shop->storefront_name,
        ];
    }
}
