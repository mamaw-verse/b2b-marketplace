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
#[Title('Privacy Policy — 1HalalStore')]
final class PrivacyPage extends Component
{
    public function render(): View
    {
        app(SeoResolver::class)->set(app(BuildContentSeoData::class)->handle(
            description: __('How :app collects, uses and protects personal data of buyers, merchants and partners.', ['app' => config('app.name')]),
        ));

        return view('livewire.legal.privacy-page');
    }
}
