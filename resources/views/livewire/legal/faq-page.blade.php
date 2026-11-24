<style>
    html { scroll-behavior: smooth; }
    @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
</style>
<div id="top" class="bg-[var(--hs-neutral-50)]" x-data="{ open: 'faq-1', query: '' }">
    {{-- Hero band --}}
    <div class="bg-[var(--hs-green-950)]">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pt-10 sm:pt-14 pb-8 sm:pb-10">
            <nav class="flex items-center gap-2 text-xs text-white/50" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" wire:navigate class="hover:text-[var(--hs-gold-300)] transition">{{ __('Home') }}</a>
                <span aria-hidden="true">/</span>
                <span class="font-semibold text-white/90">{{ __('FAQs') }}</span>
            </nav>
            <h1 class="mt-4 text-2xl sm:text-3xl font-bold tracking-tight text-white">{{ __('Frequently Asked Questions') }}</h1>
            <p class="mt-2 max-w-2xl text-sm sm:text-base text-white/70 leading-relaxed">
                {{ __('Halal Supply-Chain Marketplace — from Farm to Plate. Answers for buyers, merchants, logistics partners and country operators.') }}
            </p>
            {{-- Search --}}
            <div class="relative mt-5 max-w-xl">
                <flux:icon.magnifying-glass class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-white/40 pointer-events-none" />
                <input
                    x-model="query"
                    type="search"
                    placeholder="{{ __('Search FAQs') }}"
                    class="w-full pl-12 pr-4 py-3 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder:text-white/40 focus:bg-white/15 focus:border-[var(--hs-gold-400)] focus:ring-2 focus:ring-[var(--hs-gold-400)]/20 outline-none transition"
                />
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 sm:py-10">

        {{-- Mobile topics --}}
        <details class="mb-6 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] lg:hidden">
            <summary class="cursor-pointer px-5 py-3.5 text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('Browse topics') }}</summary>
            <ul class="space-y-0.5 border-t border-[var(--hs-neutral-100)] px-3 py-3 text-sm">
                @foreach ($groups as $group)
                    <li>
                        <a href="#group-{{ $group['id'] }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-[0.85rem] font-medium text-[var(--hs-neutral-600)] hover:bg-[var(--hs-green-50)] hover:text-[var(--hs-green-700)] transition">
                            {{ $group['title'] }}
                            <span class="rounded-full bg-[var(--hs-neutral-100)] px-2 py-0.5 text-[0.7rem] font-bold text-[var(--hs-neutral-500)]">{{ count($group['items']) }}</span>
                        </a>
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
                @include('partials.legal.doc-nav', ['active' => 'faq'])

                <div class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4">
                    <h2 class="px-1 text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Browse topics') }}</h2>
                    <ul class="mt-3 space-y-0.5 text-sm">
                        @foreach ($groups as $group)
                            <li>
                                <a
                                    href="#group-{{ $group['id'] }}"
                                    :class="active === 'group-{{ $group['id'] }}' ? 'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] font-semibold' : 'text-[var(--hs-neutral-600)]'"
                                    class="flex items-center justify-between rounded-lg px-3 py-2 text-[0.85rem] font-medium hover:bg-[var(--hs-green-50)] hover:text-[var(--hs-green-700)] transition"
                                >
                                    {{ $group['title'] }}
                                    <span class="rounded-full bg-[var(--hs-neutral-100)] px-2 py-0.5 text-[0.7rem] font-bold text-[var(--hs-neutral-500)]">{{ count($group['items']) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-green-50)] p-5">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-[var(--hs-neutral-900)]">
                        <flux:icon.lifebuoy class="size-4 text-[var(--hs-green-700)]" />
                        {{ __('Still stuck?') }}
                    </h2>
                    <p class="mt-2 text-[0.8rem] leading-relaxed text-[var(--hs-neutral-600)]">{{ __('Contact customer support with your order or business details and we will help.') }}</p>
                </div>
            </aside>

            {{-- Topic groups --}}
            <div class="min-w-0 space-y-6">
                @foreach ($groups as $group)
                    <section id="group-{{ $group['id'] }}" class="rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-6 sm:p-8 shadow-[var(--hs-shadow-sm)] scroll-mt-24">
                        <h2 class="flex items-center gap-3 text-lg font-bold text-[var(--hs-neutral-900)]">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[var(--hs-green-50)] text-[var(--hs-green-700)]">
                                <flux:icon :icon="$group['icon']" class="size-5" />
                            </span>
                            {{ $group['title'] }}
                            <span class="rounded-full bg-[var(--hs-neutral-100)] px-2.5 py-0.5 text-[0.72rem] font-bold text-[var(--hs-neutral-500)]">{{ count($group['items']) }}</span>
                        </h2>
                        <div class="mt-5 space-y-3">
                            @foreach ($group['items'] as $item)
                                <div
                                    x-show="!query || '{{ mb_strtolower($item['q']) }}'.includes(query.toLowerCase())"
                                    class="overflow-hidden rounded-xl border transition"
                                    :class="open === '{{ $item['id'] }}' ? 'border-[var(--hs-green-200)] bg-[var(--hs-green-50)]/40' : 'border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]'"
                                >
                                    <button
                                        type="button"
                                        @click="open = open === '{{ $item['id'] }}' ? null : '{{ $item['id'] }}'"
                                        :aria-expanded="open === '{{ $item['id'] }}'"
                                        class="flex w-full items-center justify-between gap-3 bg-transparent border-none cursor-pointer px-4 sm:px-5 py-4 text-left"
                                    >
                                        <span class="text-sm sm:text-[0.92rem] font-semibold text-[var(--hs-neutral-900)]">{{ $item['q'] }}</span>
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-600)] transition-transform" :class="open === '{{ $item['id'] }}' && 'rotate-180'">
                                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                        </span>
                                    </button>
                                    <div x-show="open === '{{ $item['id'] }}'" x-collapse>
                                        <p class="px-4 sm:px-5 pb-4 sm:pb-5 text-sm leading-relaxed text-[var(--hs-neutral-700)]">{{ $item['a'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                {{-- Back to documents --}}
                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('legal.privacy') }}" wire:navigate class="group flex items-center justify-between gap-4 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-5 py-4 no-underline transition hover:border-[var(--hs-green-200)] hover:bg-[var(--hs-green-50)]/50 sm:min-w-64">
                        <span class="text-[var(--hs-neutral-400)] group-hover:text-[var(--hs-green-700)] transition" aria-hidden="true">&larr;</span>
                        <span class="text-right">
                            <span class="block text-xs font-medium uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Previous') }}</span>
                            <span class="mt-0.5 block text-sm font-bold text-[var(--hs-neutral-900)] group-hover:text-[var(--hs-green-700)]">{{ __('Privacy Policy') }}</span>
                        </span>
                    </a>
                    <a href="#top" class="text-xs font-semibold text-[var(--hs-neutral-500)] hover:text-[var(--hs-green-700)] transition sm:text-right">{{ __('Back to top') }} &uarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
