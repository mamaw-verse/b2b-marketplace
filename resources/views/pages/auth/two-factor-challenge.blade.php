<x-layouts::auth :title="__('Two-factor authentication')">
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-[var(--hs-neutral-900)]" style="font-family: var(--hs-font-display);">{{ __('Two-factor authentication') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Enter the code from your authenticator app or use a recovery code') }}</p>
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-5">
            @csrf
            <div x-data="{ recovery: false }">
                <div x-show="!recovery">
                    <label for="code" class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">{{ __('Authentication code') }}</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autofocus autocomplete="one-time-code" placeholder="000000"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-[var(--hs-neutral-200)] text-sm text-center tracking-widest focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)]/20 focus:border-[var(--hs-green-600)] transition" />
                </div>

                <div x-show="recovery" x-cloak>
                    <label for="recovery_code" class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">{{ __('Recovery code') }}</label>
                    <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-[var(--hs-neutral-200)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)]/20 focus:border-[var(--hs-green-600)] transition" />
                </div>

                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('recovery_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                <button type="button" x-on:click="recovery = !recovery" class="mt-3 text-xs font-medium text-[var(--hs-green-700)] hover:underline">
                    <span x-show="!recovery">{{ __('Use a recovery code') }}</span>
                    <span x-show="recovery">{{ __('Use authentication code') }}</span>
                </button>
            </div>

            <button type="submit" class="w-full py-2.5 rounded-lg bg-[var(--hs-green-600)] hover:bg-[var(--hs-green-700)] text-white text-sm font-semibold transition shadow-[var(--hs-shadow-sm)]">
                {{ __('Verify') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
