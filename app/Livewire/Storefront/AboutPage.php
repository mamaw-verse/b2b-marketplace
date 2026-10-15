<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

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
        return view('livewire.storefront.about-page');
    }
}
