<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\SponsoredContent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('1Halal Store — Discover Halal-Certified Companies & Products')]
final class HeroSearch extends Component
{
    public string $query = '';

    /** @var SupportCollection<int, Product> */
    public SupportCollection $productResults;

    public function updatedQuery(): void
    {
        $this->search();
    }

    public function search(): void
    {
        if (mb_strlen($this->query) < 2) {
            $this->productResults = new SupportCollection;

            return;
        }

        $this->productResults = Product::search($this->query)
            ->query(fn (Builder $q) => $q->with(['shop', 'variants']))
            ->take(8)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function featuredProducts(): Collection
    {
        return Product::marketplaceVisible()
            ->with(['shop', 'variants'])
            ->latest()
            ->take(8)
            ->get();
    }

    /**
     * Top-level category groups for the hero pills.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::roots()->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Active sponsored content with tall creatives for the hero carousel.
     * Loaded directly here so slides render as native blade without a nested Livewire component.
     *
     * @return Collection<int, SponsoredContent>
     */
    #[Computed]
    public function heroSponsoredSlides(): Collection
    {
        if (! config('marketplace.features.sponsored_content_enabled')) {
            return new Collection;
        }

        return SponsoredContent::with('tallMedia')
            ->active()
            ->forPlacement('homepage')
            ->whereNotNull('tall_media_id')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * All known country portals from config, excluding the current instance.
     *
     * @return array<int, array<string, string|null>>
     */
    #[Computed]
    public function otherPortals(): array
    {
        $instanceCountry = config('marketplace.instance_country');

        return collect(config('marketplace.portals', []))
            ->reject(fn (array $p) => $p['country_code'] === $instanceCountry)
            ->values()
            ->all();
    }

    /**
     * The current instance's portal entry from config.
     *
     * @return array<string, string|null>
     */
    #[Computed]
    public function instancePortal(): array
    {
        $instanceCountry = config('marketplace.instance_country');

        return collect(config('marketplace.portals', []))
            ->firstWhere('country_code', $instanceCountry)
            ?? ['country_code' => $instanceCountry, 'display_name' => $instanceCountry, 'flag' => '🌐', 'locale' => 'en', 'url' => null];
    }

    public function render(): View
    {
        return view('livewire.storefront.hero-search');
    }
}
