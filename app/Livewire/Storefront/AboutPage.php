<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Actions\Seo\BuildContentSeoData;
use App\Support\Schema\SchemaId;
use App\Support\Seo\SeoResolver;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('About Us — 1HalalStore')]
final class AboutPage extends Component
{
    public function render(): View
    {
        app(SeoResolver::class)->set(app(BuildContentSeoData::class)->handle(
            description: __('Learn about :app — the trusted halal supply-chain marketplace connecting farmers, producers, certifiers, and retailers in one ecosystem.', ['app' => config('app.name')]),
            title: __('About Us — :app', ['app' => config('app.name')]),
            pageType: 'AboutPage',
            crumb: __('About Us'),
            crumbUrl: route('about'),
            mainEntityId: SchemaId::organization(),
        ));

        return view('livewire.storefront.about-page');
    }
}
