<div class="mx-auto max-w-xl px-6 py-16">
    <div class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-8">
        @if (! $this->invitation)
            <h1 class="text-2xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Invitation unavailable') }}</h1>
            <p class="mt-3 text-[var(--hs-neutral-600)]">{{ __('This invitation is invalid, expired, or has already been used. Ask the shop owner to send a new invitation.') }}</p>
        @else
            <h1 class="text-2xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Join :shop', ['shop' => $this->invitation->shop->storefront_name]) }}</h1>
            <p class="mt-3 text-[var(--hs-neutral-600)]">{{ __('You were invited as :email.', ['email' => $this->invitation->email]) }}</p>
            @auth
                @if (mb_strtolower(auth()->user()->email) !== mb_strtolower($this->invitation->email))
                    <p class="mt-4 text-[var(--hs-warning-700)]">{{ __('You are signed in as :current, but this invitation was sent to :invited.', ['current' => auth()->user()->email, 'invited' => $this->invitation->email]) }}</p>
                    <form class="mt-4" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="underline" type="submit">{{ __('Sign out and use another account') }}</button>
                    </form>
                @else
                    <flux:button class="mt-6" variant="primary" wire:click="accept">{{ __('Join team') }}</flux:button>
                    @error('invitation') <p class="mt-3 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
                @endif
            @else
                <div class="mt-6 flex gap-4">
                    <a class="underline" href="{{ route('login', ['email' => $this->invitation->email]) }}">{{ __('Sign in') }}</a>
                    <a class="underline" href="{{ route('staff.invitations.register', $this->token) }}">{{ __('Create account') }}</a>
                </div>
            @endauth
        @endif
    </div>
</div>
