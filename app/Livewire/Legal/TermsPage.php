<?php

declare(strict_types=1);

namespace App\Livewire\Legal;

use App\Actions\Seo\BuildContentSeoData;
use App\Support\Seo\SeoResolver;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Terms of Service — 1HalalStore')]
final class TermsPage extends Component
{
    public function render(): View
    {
        app(SeoResolver::class)->set(app(BuildContentSeoData::class)->handle(
            description: __('The terms of service for buyers, merchants and partners using :app, the halal supply-chain marketplace.', ['app' => config('app.name')]),
        ));

        return view('livewire.legal.terms-page');
    }
}
