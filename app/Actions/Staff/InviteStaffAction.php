<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InviteStaffAction
{
    public function execute(Shop $shop, User $inviter, string $email): ShopStaff
    {
        $token = Str::random(64);
        $membership = DB::transaction(function () use ($shop, $inviter, $email, $token): ShopStaff {
            $lockedShop = Shop::query()->whereKey($shop->id)->lockForUpdate()->firstOrFail();
            abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
            abort_unless($lockedShop->user_id === $inviter->id, 403);

            $normalizedEmail = mb_strtolower(trim($email));
            $existing = User::query()->whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
            if (($existing && ($existing->isShopOwner() || $existing->hasRole('admin') || $existing->staffMembership()->exists()))
                || $normalizedEmail === mb_strtolower($inviter->email)) {
                throw ValidationException::withMessages(['email' => __('This email can\'t be invited to your team.')]);
            }

            $pending = $lockedShop->staff()->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->where('status', 'invited')->first();
            if ($pending && $pending->expires_at?->isFuture()) {
                throw ValidationException::withMessages(['email' => __('An invitation is already pending for this email.')]);
            }
            if ($pending) {
                $pending->update(['status' => 'revoked', 'token_hash' => null]);
            }

            if ($lockedShop->staffSeatsUsed() >= (int) config('marketplace.limits.staff_per_shop', 2)) {
                throw ValidationException::withMessages(['email' => __('Your team has no available seats.')]);
            }

            $staff = $lockedShop->staff()->create([
                'email' => $normalizedEmail,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
                'invited_by_id' => $inviter->id,
                'status' => 'invited',
            ]);

            DB::afterCommit(fn () => Notification::route('mail', $normalizedEmail)
                ->notify(new StaffInvitationNotification($lockedShop, $inviter, $token)));

            return $staff;
        });

        return $membership;
    }
}
