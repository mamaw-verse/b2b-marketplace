<div>
    @if ($fullPage)
        <div class="bg-[var(--hs-neutral-50)]">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1.7fr_1fr] lg:items-start">

                    {{-- Main sourcing card --}}
                    <div class="rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-6 sm:p-8 shadow-[var(--hs-shadow-sm)]">
                        @if (! $submitted)
                            <h1 class="text-2xl font-bold tracking-tight text-[var(--hs-neutral-900)]">{{ __('Can' . "\u{2019}" . 't Find What You' . "\u{2019}" . 're Looking For?') }}</h1>
                            <p class="mt-1 text-sm text-[var(--hs-neutral-600)] mb-4">
                                {{ __('We can source it for you. Tell us what you need, and our sourcing team will work with our supplier network to find suitable options based on your requirements, quantity, and target budget.') }}
                            </p>
                        @endif

                        @include('livewire.storefront.partials.sourcing-form-card')
                    </div>

                    {{-- Right: How It Works panel --}}
                    <aside class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5 sm:p-6">
                        <h2 class="flex items-center gap-2 text-base font-semibold text-[var(--hs-neutral-900)]">
                            <flux:icon name="information-circle" class="size-5 text-[var(--hs-green-700)]" />
                            {{ __('How It Works') }}
                        </h2>

                        <ul class="mt-4 space-y-3.5">
                            @foreach ([
                                __('Provide as much detail as possible about the product you need.'),
                                __('A product name, description, photo, or reference link is enough to get started.'),
                                __('Our sourcing team will search our supplier network for matching products.'),
                                __('We will contact you with suitable options based on your requirements.'),
                            ] as $note)
                                <li class="flex items-start gap-2.5 text-[0.85rem] leading-relaxed text-[var(--hs-neutral-700)]">
                                    <flux:icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-[var(--hs-green-700)]" />
                                    {{ $note }}
                                </li>
                            @endforeach
                        </ul>

                        <hr class="my-5 border-[var(--hs-neutral-200)]">

                        <div class="rounded-lg bg-[var(--hs-green-50)] p-4">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-[var(--hs-neutral-900)]">
                                <flux:icon name="lifebuoy" class="size-4 text-[var(--hs-green-700)]" />
                                {{ __('Need Help?') }}
                            </h3>
                            <p class="mt-2 text-[0.8rem] leading-relaxed text-[var(--hs-neutral-600)]">
                                {{ __('If you have any questions or need assistance with the form, please reach out to our support team.') }}
                            </p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-6 sm:p-8 shadow-[var(--hs-shadow-sm)]">
            @include('livewire.storefront.partials.sourcing-form-card')
        </div>
    @endif
</div>
