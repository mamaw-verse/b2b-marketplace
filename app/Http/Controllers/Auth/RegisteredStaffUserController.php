<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Staff\AcceptStaffInvitationAction;
use App\Concerns\PasswordValidationRules;
use App\Models\ShopStaff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

final class RegisteredStaffUserController
{
    use PasswordValidationRules;

    /**
     * Show the staff registration form for the given invitation token.
     */
    public function create(string $token): View|RedirectResponse
    {
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
        abort_if(! preg_match('/^[A-Za-z0-9]{64}$/', $token), 404);

        $invitation = $this->findValidInvitation($token);

        if (! $invitation) {
            return redirect()->route('staff.invitations.show', $token);
        }

        // If a user already exists for this email, send them to log in instead.
        $existingUser = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation->email)])->first();
        if ($existingUser) {
            return redirect()->route('login', ['email' => $invitation->email]);
        }

        return view('pages.auth.staff-register', [
            'token' => $token,
            'invitation' => $invitation,
        ]);
    }

    /**
     * Create a pre-verified user account and immediately accept the invitation.
     */
    public function store(Request $request, string $token, AcceptStaffInvitationAction $action): RedirectResponse
    {
        abort_unless((bool) config('marketplace.features.staff_enabled'), 404);
        abort_if(! preg_match('/^[A-Za-z0-9]{64}$/', $token), 404);

        $invitation = $this->findValidInvitation($token);

        if (! $invitation) {
            return redirect()->route('staff.invitations.show', $token)
                ->withErrors(['invitation' => __('This invitation is invalid or has expired.')]);
        }

        Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'password' => $this->accountCreationPasswordRules(),
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => __('You must agree to the Terms of Service and Privacy Policy.'),
        ])->validate();

        $user = DB::transaction(function () use ($request, $token, $invitation, $action): User {
            $user = User::create([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $invitation->email,
                'password' => Hash::make($request->input('password')),
                'email_verified_at' => now(),
            ]);

            $action->execute($token, $user);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('portal.dashboard');
    }

    private function findValidInvitation(string $token): ?ShopStaff
    {
        return ShopStaff::query()
            ->with('shop')
            ->where('token_hash', hash('sha256', $token))
            ->where('status', 'invited')
            ->where('expires_at', '>', now())
            ->first();
    }
}
