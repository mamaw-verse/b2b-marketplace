<x-layouts::auth.card :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-[var(--hs-neutral-900)]" style="font-family: var(--hs-font-display);">{{ __('Reset your password') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Choose a new password for your account') }}</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <div>
                <label for="email" class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">{{ __('Email address') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email', request()->email) }}" required autofocus autocomplete="email"
                    class="w-full px-3.5 py-2.5 rounded-lg border border-[var(--hs-neutral-200)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)]/20 focus:border-[var(--hs-green-600)] transition" />
                @error('email') <p class="mt-1 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ show: false }">
                <label for="password" class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">{{ __('New password') }}</label>
                <div class="relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="new-password" placeholder="••••••••"
                        class="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-[var(--hs-neutral-200)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)]/20 focus:border-[var(--hs-green-600)] transition" />
                    <button
                        type="button"
                        @click="show = !show"
                        :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)]"
                    >
                        <flux:icon.eye x-show="!show" class="size-5" />
                        <flux:icon.eye-slash x-show="show" x-cloak class="size-5" />
                    </button>
                </div>
                @error('password') <p class="mt-1 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ show: false }">
                <label for="password_confirmation" class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">{{ __('Confirm password') }}</label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" required autocomplete="new-password" placeholder="••••••••"
                        class="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-[var(--hs-neutral-200)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)]/20 focus:border-[var(--hs-green-600)] transition" />
                    <button
                        type="button"
                        @click="show = !show"
                        :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-700)]"
                    >
                        <flux:icon.eye x-show="!show" class="size-5" />
                        <flux:icon.eye-slash x-show="show" x-cloak class="size-5" />
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-2.5 rounded-lg bg-[var(--hs-green-600)] hover:bg-[var(--hs-green-700)] text-white text-sm font-semibold transition shadow-[var(--hs-shadow-sm)]">
                {{ __('Reset password') }}
            </button>
        </form>
    </div>
</x-layouts::auth.card>
