<?php

declare(strict_types=1);

namespace App\Livewire\Legal;

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
        return view('livewire.legal.privacy-page');
    }
}
