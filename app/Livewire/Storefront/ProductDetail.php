<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Actions\Seo\BuildProductSeoData;
use App\Actions\Storefront\RecordProductShareAction;
use App\Models\Product;
use App\Support\Seo\SeoResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Throwable;

#[Layout('layouts.storefront')]
final class ProductDetail extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $product->loadMissing('shop');

        $visible = $product->status === 'published'
            && $product->moderation_status === 'active'
            && $product->shop->status === 'active';

        if ($visible && config('marketplace.features.subscriptions_enabled')) {
            $visible = $product->shop->hasActiveSubscription();
        }

        abort_unless($visible, 404);

        $this->product = $product;
        $this->product->load([
            // Only published variants are shown publicly; archived variants
            // must never appear on the storefront (uses the existing scope).
            'variants' => fn ($query) => $query->published(),
            'gallery',
            'documents',
            'video',
            'categories.parent',
            'shop',
            'shop.certificates',
        ]);
    }

    public function render(): View
    {
        app(SeoResolver::class)->set(app(BuildProductSeoData::class)->handle($this->product));

        return view('livewire.storefront.product-detail')
            ->title($this->product->name);
    }

    #[Renderless]
    public function trackShare(string $channel, RecordProductShareAction $action): void
    {
        if (! config('marketplace.features.product_share_enabled')) {
            return;
        }

        $key = 'product-share:'.(auth()->id() ?? request()->ip());
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return;
        }
        RateLimiter::hit($key, 60);

        try {
            $fingerprint = sha1((auth()->id() ?? request()->ip()).'|'.request()->userAgent());
            $action->execute($this->product, $channel, $fingerprint);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
