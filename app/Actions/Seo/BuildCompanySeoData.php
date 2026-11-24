<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Models\Shop;
use App\Support\Seo\SeoData;
use Illuminate\Support\Str;

final class BuildCompanySeoData
{
    /**
     * Reads only the public-data allow-list of Shop (name, description); never
     * contact, address or registration fields.
     */
    public function handle(Shop $shop): SeoData
    {
        $description = filled($shop->company_description)
            ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $shop->company_description)) ?? ''), 160)
            : __('Discover :name on :app — a verified halal-certified business on the marketplace.', ['name' => $shop->storefront_name, 'app' => config('app.name')]);

        return new SeoData(
            title: $shop->storefront_name.' — '.config('app.name'),
            description: $description,
            canonical: route('storefront.company', $shop),
            // Kept as-is from the previous markup; revisited in Phase 4 (decision D5).
            ogType: 'business.business',
            imageAlt: $shop->storefront_name,
        );
    }

    public function title(Shop $shop): string
    {
        return $shop->storefront_name.' — '.config('app.name');
    }
}
