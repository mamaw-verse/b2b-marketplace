<x-layouts::portal :title="__('Confirm identity')" portal="account">
    <section class="w-full">
        <x-pages::settings.layout :heading="__('Confirm identity')" :subheading="__('For your security, confirm your identity before continuing')">
            <div class="space-y-5">
                @if ($hasPassword)
                    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <flux:input
                                name="password"
                                :label="__('Password')"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="{{ __('Enter your current password') }}"
                                viewable
                            />
                            @error('password')
                                <p class="mt-1 text-sm text-[var(--hs-danger-500)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <flux:button variant="primary" type="submit" class="w-full">
                            {{ __('Confirm with password') }}
                        </flux:button>
                    </form>
                @endif

                @if ($hasPasskeys)
                    @if ($hasPassword)
                        <flux:separator text="{{ __('or') }}" />
                    @endif

                    <x-passkey-verify
                        options-route="passkey.confirm-options"
                        submit-route="passkey.confirm"
                        :label="__('Confirm with a passkey')"
                        :loading-label="__('Confirming passkey...')"
                        :autofill="false"
                    />
                @endif

                @if ($hasGoogleIdentity)
                    @if ($hasPassword || $hasPasskeys)
                        <flux:separator text="{{ __('or') }}" />
                    @endif

                    <flux:button
                        :href="route('auth.google.confirm')"
                        variant="filled"
                        icon="arrow-top-right-on-square"
                        class="w-full"
                    >
                        {{ __('Confirm with Google') }}
                    </flux:button>

                    @error('google')
                        <p role="alert" class="text-sm text-[var(--hs-danger-700)]">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        </x-pages::settings.layout>
    </section>
</x-layouts::portal>
