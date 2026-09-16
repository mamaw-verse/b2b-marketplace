<?php

declare(strict_types=1);

namespace App\Livewire\Legal;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Frequently Asked Questions — 1HalalStore')]
final class FaqPage extends Component
{
    public function render(): View
    {
        return view('livewire.legal.faq-page');
    }
}
