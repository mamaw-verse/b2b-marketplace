<style>
    html { scroll-behavior: smooth; }
    @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
</style>
<div id="top" class="bg-[var(--hs-neutral-50)]">
    {{-- Hero band --}}
    <div class="bg-[var(--hs-green-950)]">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pt-10 sm:pt-14 pb-8 sm:pb-10">
            <nav class="flex items-center gap-2 text-xs text-white/50" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" wire:navigate class="hover:text-[var(--hs-gold-300)] transition">{{ __('Home') }}</a>
                <span aria-hidden="true">/</span>
                <span class="font-semibold text-white/90">{{ __('Privacy Policy') }}</span>
            </nav>
            <h1 class="mt-4 text-2xl sm:text-3xl font-bold tracking-tight text-white">{{ __('Privacy Policy') }}</h1>
            <p class="mt-2 max-w-2xl text-sm sm:text-base text-white/70 leading-relaxed">
                {{ __('How 1HALALSTORE.COM collects, uses, stores, discloses and protects personal information — aligned with the Philippine Data Privacy Act of 2012.') }}
            </p>
            <p class="mt-3 text-xs text-white/40">{{ __('Effective Date: September 16, 2026') }} &middot; {{ __('Last Updated: September 16, 2026') }}</p>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        {{-- Mobile contents --}}
        <details class="mb-6 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] lg:hidden">
            <summary class="cursor-pointer px-5 py-3.5 text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('On this page') }}</summary>
            <ul class="max-h-72 space-y-0.5 overflow-y-auto border-t border-[var(--hs-neutral-100)] px-3 py-3 text-sm">
                @foreach ([1 => __('Controller'), 2 => __('Data We Collect'), 3 => __('Purposes'), 4 => __('Legal Bases'), 5 => __('Sharing'), 6 => __('Merchant↔Buyer'), 7 => __('Halal Transparency'), 8 => __('Transfers'), 9 => __('Cookies'), 10 => __('Security'), 11 => __('Retention'), 12 => __('Your Rights'), 13 => __('Exercise Rights'), 14 => __('Children'), 15 => __('Third Parties'), 16 => __('Marketing'), 17 => __('Breaches'), 18 => __('Changes'), 19 => __('Contact')] as $n => $label)
                    <li>
                        <a href="#section-{{ $n }}" class="block rounded-lg px-3 py-1.5 text-[0.85rem] text-[var(--hs-neutral-600)] hover:bg-[var(--hs-green-50)] hover:text-[var(--hs-green-700)] transition">{{ $n }}. {{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </details>

        <div
            x-data="{ active: null }"
            x-init="() => { const obs = new IntersectionObserver((entries) => { entries.forEach((e) => { if (e.isIntersecting) active = e.target.id; }); }, { rootMargin: '-10% 0px -75% 0px' }); $el.querySelectorAll('section[id]').forEach((s) => obs.observe(s)); }"
            class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start"
        >
            {{-- Sidebar --}}
            <aside class="hidden lg:block lg:sticky lg:top-6 space-y-6">
                @include('partials.legal.doc-nav', ['active' => 'privacy'])

                <div class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4">
                    <h2 class="px-1 text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('On this page') }}</h2>
                    <ul class="mt-3 max-h-[420px] space-y-0.5 overflow-y-auto text-sm">
                        @foreach ([1 => __('Controller'), 2 => __('Data We Collect'), 3 => __('Purposes'), 4 => __('Legal Bases'), 5 => __('Sharing'), 6 => __('Merchant↔Buyer'), 7 => __('Halal Transparency'), 8 => __('Transfers'), 9 => __('Cookies'), 10 => __('Security'), 11 => __('Retention'), 12 => __('Your Rights'), 13 => __('Exercise Rights'), 14 => __('Children'), 15 => __('Third Parties'), 16 => __('Marketing'), 17 => __('Breaches'), 18 => __('Changes'), 19 => __('Contact')] as $n => $label)
                            <li>
                                <a
                                    href="#section-{{ $n }}"
                                    :class="active === 'section-{{ $n }}' ? 'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] font-semibold' : 'text-[var(--hs-neutral-600)]'"
                                    class="block rounded-lg px-3 py-1.5 text-[0.82rem] hover:bg-[var(--hs-green-50)] hover:text-[var(--hs-green-700)] transition"
                                >{{ $n }}. {{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-green-50)] p-5">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-[var(--hs-neutral-900)]">
                        <flux:icon.lifebuoy class="size-4 text-[var(--hs-green-700)]" />
                        {{ __('Your data rights') }}
                    </h2>
                    <p class="mt-2 text-[0.8rem] leading-relaxed text-[var(--hs-neutral-600)]">{{ __('To access, correct, or request deletion of your data, contact the Data Protection Officer.') }}</p>
                </div>
            </aside>

            {{-- Main card --}}
            <article class="min-w-0 rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-6 sm:p-8 shadow-[var(--hs-shadow-sm)]">
                @include('partials.legal.privacy-body')

                {{-- Prev / Next + back to top --}}
                <div class="mt-8 grid grid-cols-1 gap-3 border-t border-[var(--hs-neutral-100)] pt-6 sm:grid-cols-2">
                    <a href="{{ route('legal.terms') }}" wire:navigate class="group flex items-center justify-between gap-4 rounded-xl border border-[var(--hs-neutral-200)] px-5 py-4 no-underline transition hover:border-[var(--hs-green-200)] hover:bg-[var(--hs-green-50)]/50">
                        <span class="text-[var(--hs-neutral-400)] group-hover:text-[var(--hs-green-700)] transition" aria-hidden="true">&larr;</span>
                        <span class="text-right">
                            <span class="block text-xs font-medium uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Previous') }}</span>
                            <span class="mt-0.5 block text-sm font-bold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)]">{{ __('Terms of Service') }}</span>
                        </span>
                    </a>
                    <a href="{{ route('help.faq') }}" wire:navigate class="group flex items-center justify-between gap-4 rounded-xl border border-[var(--hs-neutral-200)] px-5 py-4 no-underline transition hover:border-[var(--hs-green-200)] hover:bg-[var(--hs-green-50)]/50">
                        <span>
                            <span class="block text-xs font-medium uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Next') }}</span>
                            <span class="mt-0.5 block text-sm font-bold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)]">{{ __('FAQs') }}</span>
                        </span>
                        <span class="text-[var(--hs-neutral-400)] group-hover:text-[var(--hs-green-700)] transition" aria-hidden="true">&rarr;</span>
                    </a>
                </div>
                <div class="mt-4 text-right">
                    <a href="#top" class="text-xs font-semibold text-[var(--hs-neutral-500)] hover:text-[var(--hs-green-700)] transition">{{ __('Back to top') }} &uarr;</a>
                </div>
            </article>
        </div>
    </div>
</div>
