<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Models\CertificationApplication;
use App\Models\User;
use App\Notifications\CertificationInterestNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Halal Certification')]
final class CertificationApplicationForm extends Component
{
    #[Computed]
    public function application(): ?CertificationApplication
    {
        return CertificationApplication::query()
            ->where('shop_id', Auth::user()->shop?->id)
            ->latest('created_at')
            ->first();
    }

    public function mount(): void
    {
        abort_unless(Auth::user()->shop !== null, 403, 'You must have a registered shop to express certification interest.');

    }

    public function submit(): void
    {
        $shop = Auth::user()->shop;
        abort_unless($shop !== null, 403);

        if ($this->application) {
            session()->flash('message', 'Your interest has already been sent to the marketplace team.');

            return;
        }

        $application = CertificationApplication::create([
            'shop_id' => $shop->id,
            'status' => 'interested',
        ]);

        User::role('admin')->get()->each(function (User $admin) use ($application): void {
            $admin->notify(new CertificationInterestNotification($application->load('shop')));
        });

        unset($this->application);

        session()->flash('message', 'Your interest in halal certification has been sent to the marketplace team.');
    }

    public function render()
    {
        return view('livewire.company-hub.certification-application-form');
    }
}
