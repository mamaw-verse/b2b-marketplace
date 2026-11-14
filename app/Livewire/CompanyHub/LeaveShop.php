<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Actions\Staff\LeaveShopAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal', ['portal' => 'hub'])]
#[Title('Leave shop team')]
final class LeaveShop extends Component
{
    public function leave(LeaveShopAction $action): mixed
    {
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
        Gate::authorize('accessHub');
        abort_unless(Auth::user()->isShopStaff(), 403);
        $action->execute(Auth::user());

        return redirect()->route('portal.dashboard')->with('status', __('You left the shop team.'));
    }

    public function render(): View
    {
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
        abort_unless(Auth::user()->isShopStaff(), 403);

        return view('livewire.company-hub.leave-shop');
    }
}
