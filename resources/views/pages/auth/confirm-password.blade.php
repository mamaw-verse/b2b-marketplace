<x-layouts::portal :title="__('Confirm Password')" portal="account">
    <section class="w-full">
        <x-pages::settings.layout :heading="__('Confirm Password')" :subheading="__('For your security, please confirm your password before continuing')">
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
                    />
                    @error('password')
                        <p class="mt-1 text-sm text-[var(--hs-danger-500)]">{{ $message }}</p>
                    @enderror
                </div>

                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Confirm & Continue') }}
                </flux:button>
            </form>
        </x-pages::settings.layout>
    </section>
</x-layouts::portal>
