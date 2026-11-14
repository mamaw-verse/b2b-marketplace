<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\ShopStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LeaveShopAction
{
    public function execute(User $user): void
    {
        $membership = $user->staffMembership;
        if (! $membership) {
            throw ValidationException::withMessages(['shop' => __('You are not a member of a shop team.')]);
        }

        DB::transaction(function () use ($membership, $user): void {
            $locked = ShopStaff::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'active') {
                $locked->update(['status' => 'left', 'removed_at' => now(), 'removed_by_id' => $user->id]);
            }
        });
    }
}
