<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\ShopStaff;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ResendStaffInvitationAction
{
    public function execute(ShopStaff $staff, User $actor): void
    {
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
        abort_unless($staff->shop->user_id === $actor->id, 403);
        $token = Str::random(64);
        DB::transaction(function () use ($staff, $actor, $token): void {
            $shop = $staff->shop()->lockForUpdate()->firstOrFail();
            $locked = ShopStaff::query()->whereKey($staff->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'invited') {
                throw ValidationException::withMessages(['invitation' => __('This invitation can no longer be resent.')]);
            }
            if (! $locked->expires_at?->isFuture()
                && $shop->staffSeatsUsed() >= (int) config('marketplace.limits.staff_per_shop', 2)) {
                throw ValidationException::withMessages(['invitation' => __('Your team has no available seats.')]);
            }
            $locked->update(['token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7), 'invited_by_id' => $actor->id]);
            DB::afterCommit(fn () => Notification::route('mail', $locked->email)
                ->notify(new StaffInvitationNotification($locked->shop, $actor, $token)));
        });
    }
}
