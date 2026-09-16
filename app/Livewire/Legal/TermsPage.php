<?php

declare(strict_types=1);

namespace App\Livewire\Legal;

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
        return view('livewire.legal.terms-page');
    }
}
