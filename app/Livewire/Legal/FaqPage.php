<?php

declare(strict_types=1);

namespace App\Livewire\Legal;

use App\Actions\Seo\BuildContentSeoData;
use App\Support\Seo\FaqEntries;
use App\Support\Seo\SeoResolver;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Frequently Asked Questions — 1HalalStore')]
final class FaqPage extends Component
{
    public function render(FaqEntries $faq): View
    {
        app(SeoResolver::class)->set(app(BuildContentSeoData::class)->handle(
            description: __('Answers to common questions from buyers, merchants, logistics partners and country operators on :app, the halal supply-chain marketplace.', ['app' => config('app.name')]),
            crumb: __('Frequently Asked Questions'),
            crumbUrl: route('help.faq'),
            pageType: 'FAQPage',
            faqEntries: $faq->flat(),
        ));

        return view('livewire.legal.faq-page', ['groups' => $faq->groups()]);
    }
}
