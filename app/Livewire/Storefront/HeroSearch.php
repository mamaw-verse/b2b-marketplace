<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('One Halal Global — Discover Halal-Certified Companies & Products')]
final class HeroSearch extends Component
{
    public string $query = '';

    /** @var array<int, mixed> */
    public array $companyResults = [];

    /** @var array<int, mixed> */
    public array $productResults = [];

    public function updatedQuery(): void
    {
        $this->search();
    }

    public function search(): void
    {
        if (mb_strlen($this->query) < 2) {
            $this->companyResults = [];
            $this->productResults = [];

            return;
        }

        $this->companyResults = Shop::search($this->query)->take(6)->get()->toArray();
        $this->productResults = Product::search($this->query)->take(6)->get()->toArray();
    }

    /**
     * @return Collection<int, Shop>
     */
    #[Computed]
    public function featuredCompanies(): Collection
    {
        return Shop::with(['categories', 'certificates'])
            ->where('status', 'active')
            ->latest()
            ->take(6)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function featuredProducts(): Collection
    {
        return Product::with(['shop', 'variants'])
            ->where('status', 'published')
            ->latest()
            ->take(8)
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'companies' => Shop::where('status', 'active')->count(),
            'products' => Product::where('status', 'published')->count(),
            'categories' => Category::count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.storefront.hero-search');
    }
}
