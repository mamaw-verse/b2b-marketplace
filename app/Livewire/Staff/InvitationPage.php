<?php

declare(strict_types=1);

namespace App\Livewire\Staff;

use App\Actions\Staff\AcceptStaffInvitationAction;
use App\Models\ShopStaff;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.storefront')]
final class InvitationPage extends Component
{
    #[Locked]
    public string $token;

    public function mount(string $token): void
    {
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
        abort_if(! preg_match('/^[A-Za-z0-9]{64}$/', $token), 404);
        $this->token = $token;

        if (! Auth::check()) {
            session(['staff_invitation_token' => $token]);
            redirect()->setIntendedUrl(request()->fullUrl());
        }
    }

    #[Computed]
    public function invitation(): ?ShopStaff
    {
        return ShopStaff::query()->with('shop')->where('token_hash', hash('sha256', $this->token))
            ->where('status', 'invited')->where('expires_at', '>', now())->first();
    }

    public function accept(AcceptStaffInvitationAction $action): mixed
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $action->execute($this->token, Auth::user());

        return redirect()->route('portal.dashboard');
    }

    public function render(): mixed
    {
        return view('livewire.staff.invitation-page');
    }
}
