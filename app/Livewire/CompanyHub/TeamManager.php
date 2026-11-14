<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Actions\Staff\InviteStaffAction;
use App\Actions\Staff\ResendStaffInvitationAction;
use App\Actions\Staff\RevokeStaffAction;
use App\Models\Shop;
use App\Models\ShopStaff;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Email;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Team')]
final class TeamManager extends Component
{
    public string $email = '';

    public ?int $confirmingStaffId = null;

    #[Computed]
    public function shop(): Shop
    {
        $shop = Auth::user()->activeShop;
        abort_unless($shop, 403);
        Gate::authorize('manageStaff', $shop);

        return $shop;
    }

    /** @return Collection<int, ShopStaff> */
    #[Computed]
    public function activeStaff(): Collection
    {
        return $this->shop->activeStaff()->with('user')->orderBy('created_at')->get();
    }

    /** @return Collection<int, ShopStaff> */
    #[Computed]
    public function pendingInvitations(): Collection
    {
        return $this->shop->staff()->where('status', 'invited')->with('inviter')->orderBy('created_at')->get();
    }

    public function invite(InviteStaffAction $action): void
    {
        Gate::authorize('manageStaff', $this->shop);
        $validated = $this->validate(['email' => ['required', 'string', 'max:255', new Email]]);
        $action->execute($this->shop, Auth::user(), $validated['email']);
        $this->reset('email');
        $this->clearTeamCache();
        $this->dispatch('toast', message: __('Invitation sent.'));
    }

    public function confirmRevoke(int $staffId): void
    {
        Gate::authorize('manageStaff', $this->shop);
        $this->confirmingStaffId = $staffId;
        Flux::modal('team-revoke-confirm')->show();
    }

    public function revoke(RevokeStaffAction $action): void
    {
        Gate::authorize('manageStaff', $this->shop);
        $staff = $this->shop->staff()->findOrFail($this->confirmingStaffId);
        $action->execute($staff, Auth::user());
        $this->confirmingStaffId = null;
        $this->clearTeamCache();
        Flux::modal('team-revoke-confirm')->close();
        $this->dispatch('toast', message: __('Team access removed.'));
    }

    public function resend(int $staffId, ResendStaffInvitationAction $action): void
    {
        Gate::authorize('manageStaff', $this->shop);
        $staff = $this->shop->staff()->findOrFail($staffId);
        $action->execute($staff, Auth::user());
        $this->clearTeamCache();
        $this->dispatch('toast', message: __('Invitation sent again.'));
    }

    private function clearTeamCache(): void
    {
        unset($this->activeStaff, $this->pendingInvitations, $this->shop);
    }

    public function render(): View
    {
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);

        return view('livewire.company-hub.team-manager');
    }
}
