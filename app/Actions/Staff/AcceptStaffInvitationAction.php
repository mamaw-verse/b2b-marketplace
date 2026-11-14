<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\ShopStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcceptStaffInvitationAction
{
    public function execute(string $token, User $user): ShopStaff
    {
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);

        return DB::transaction(function () use ($token, $user): ShopStaff {
            $invitation = ShopStaff::query()->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (! $invitation || ! hash_equals((string) $invitation->token_hash, hash('sha256', $token))
                || $invitation->status !== 'invited' || ! $invitation->expires_at?->isFuture()) {
                throw ValidationException::withMessages(['invitation' => __('This invitation is invalid or has expired.')]);
            }

            if (mb_strtolower($user->email) !== mb_strtolower($invitation->email)) {
                throw ValidationException::withMessages(['invitation' => __('Sign in with the email address this invitation was sent to.')]);
            }
            if ($user->isShopOwner() || $user->hasRole('admin') || $user->staffMembership()->exists()) {
                throw ValidationException::withMessages(['invitation' => __('This invitation is invalid or has expired.')]);
            }
            abort_unless($invitation->shop()->where('status', 'active')->exists(), 403);

            $invitation->update([
                'user_id' => $user->id,
                'status' => 'active',
                'accepted_at' => now(),
                'token_hash' => null,
                'expires_at' => null,
            ]);

            return $invitation->fresh();
        });
    }
}
