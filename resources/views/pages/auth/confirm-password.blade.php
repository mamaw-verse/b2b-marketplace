<x-layouts::auth :title="__('Confirm password')">
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-[var(--hs-neutral-900)]" style="font-family: var(--hs-font-display);">{{ __('Confirm your password') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Please confirm your password before continuing') }}</p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-5">
            @csrf
            <div>
                <label for="password" class="block text-sm font-semibold text-[var(--hs-neutral-700)] mb-1.5">{{ __('Password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••"
                    class="w-full px-3.5 py-2.5 rounded-lg border border-[var(--hs-neutral-200)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)]/20 focus:border-[var(--hs-green-600)] transition" />
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full py-2.5 rounded-lg bg-[var(--hs-green-600)] hover:bg-[var(--hs-green-700)] text-white text-sm font-semibold transition shadow-[var(--hs-shadow-sm)]">
                {{ __('Confirm') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
