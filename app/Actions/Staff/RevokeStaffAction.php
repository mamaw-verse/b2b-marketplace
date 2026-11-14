<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\ShopStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RevokeStaffAction
{
    public function execute(ShopStaff $staff, User $actor): void
    {
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
        abort_unless($staff->shop->user_id === $actor->id || $actor->hasRole('admin'), 403);
        DB::transaction(function () use ($staff, $actor): void {
            $locked = ShopStaff::query()->whereKey($staff->id)->lockForUpdate()->firstOrFail();
            $locked->update(['status' => 'revoked', 'token_hash' => null, 'removed_at' => now(), 'removed_by_id' => $actor->id]);
        });
    }
}
