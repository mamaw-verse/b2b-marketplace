{{-- Sticky Header (shared between storefront and portal layouts) --}}
@php
    $isAdminOrHub = request()->routeIs('admin.*') || request()->routeIs('hub.*');
    $isStorefront = ! $isAdminOrHub;
    $user = auth()->user();
    $isAdmin = $user?->hasRole('admin');
    $shop = $user?->activeShop;
    // Seller access is driven by an approved (active) shop, not the Spatie role.
    // Onboarding approval sets shops.status = 'active' but does not assign a role,
    // so gating on shop status keeps the header in sync with the sidebar.
    $isSeller = $shop?->status === 'active';
@endphp
<header class="sticky top-0 z-50 border-b border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]/80 backdrop-blur-md">
    {{-- Auth user ID for Echo notification channel (safe to expose — it's the PK, not a secret) --}}
    @auth
        <meta name="auth-user-id" content="{{ $user->id }}">
    @endauth
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative flex h-16 items-center justify-between">

            {{-- Left: Hamburger (portal mobile only) + Logo --}}
            @php
                // Show the mobile-nav trigger on every portal page (admin, seller hub,
                // buyer hub and shared settings pages). $portal is only set by the
                // portal layout, so storefront pages never render the hamburger.
                $isPortal = $isAdminOrHub || request()->routeIs('account.*') || isset($portal);
            @endphp
            <div class="flex items-center gap-2 shrink-0">
                @if ($isPortal)
                    <button
                        x-data
                        @click="window.dispatchEvent(new CustomEvent('toggle-portal-nav'))"
                        class="lg:hidden flex size-9 items-center justify-center rounded-lg text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] transition"
                        aria-label="{{ __('Open navigation menu') }}"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                @endif
                @unless ($isPortal)
                    {{-- Storefront mobile hamburger — opens the drawer defined in storefront.blade.php --}}
                    <button
                        @click="sfNav = true"
                        class="sm:hidden flex size-9 items-center justify-center rounded-lg text-[var(--hs-neutral-600)] transition"
                        aria-label="{{ __('Open menu') }}"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                @endunless
                {{-- Logo: shown on mobile for storefront (small), always shown on portal --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 mr-1" wire:navigate>
                    <img src="/images/brand-logo.png" alt="1Halal Store" class="h-7 sm:h-9 w-auto">
                    @unless ($isPortal)
                        @php
                            $flagMap = ['PH'=>'🇵🇭','MY'=>'🇲🇾','SA'=>'🇸🇦','BN'=>'🇧🇳','ID'=>'🇮🇩','TR'=>'🇹🇷'];
                            $flag = $flagMap[config('marketplace.instance_country')] ?? '🌐';
                        @endphp
                        <span class="hidden sm:inline-flex items-start border-l-2 border-[var(--hs-neutral-300)] h-7 size-8 text-lg leading-none" title="{{ config('marketplace.instance_country') }}">
                            {{ $flag }}
                        </span>
                    @endunless
                </a>

                {{-- Portal context label — single unified label for all non-admin portal pages. --}}
                @auth
                    @if ($isPortal && ! $isAdmin)
                        <div class="hidden sm:flex items-center gap-3 pl-1">
                            <span class="h-8 w-px bg-[var(--hs-neutral-200)]"></span>
                            <div class="flex flex-col leading-tight">
                                <span class="text-base font-bold text-[var(--hs-green-600)] truncate max-w-[240px]">
                                    {{ $shop?->storefront_name ?? trim($user->first_name . ' ' . $user->last_name) }}
                                </span>
                                <span class="text-xs font-bold uppercase tracking-wider text-[var(--hs-gold-700)]">
                                    {{ __('My Portal') }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endauth
            </div>

            {{-- Right: Nav links + Auth (nav hidden on admin/hub) --}}
            <div class="flex items-center gap-1">

                @unless ($isPortal)
                    <nav class="hidden sm:flex items-center absolute left-1/2 -translate-x-1/2">
                        <a
                            href="{{ route('storefront.products') }}"
                            wire:navigate
                            @class([
                                'relative px-3 py-2 text-sm font-medium transition pb-[calc(0.5rem+2px)]',
                                'text-[var(--hs-neutral-900)] font-semibold after:absolute after:bottom-0 after:left-3 after:right-3 after:h-0.5 after:bg-[var(--hs-gold-500)] after:rounded-full' => request()->routeIs('storefront.products') || request()->routeIs('storefront.product'),
                                'text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)]' => ! (request()->routeIs('storefront.products') || request()->routeIs('storefront.product')),
                            ])
                        >
                            {{ __('Products') }}
                        </a>
                        <a
                            href="{{ route('apply.certificate') }}"
                            wire:navigate
                            @class([
                                'relative px-3 py-2 text-sm font-medium transition pb-[calc(0.5rem+2px)]',
                                'text-[var(--hs-neutral-900)] font-semibold after:absolute after:bottom-0 after:left-3 after:right-3 after:h-0.5 after:bg-[var(--hs-gold-500)] after:rounded-full' => request()->routeIs('apply.certificate'),
                                'text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)]' => ! request()->routeIs('apply.certificate'),
                            ])
                        >
                            {{ __('Certification') }}
                        </a>
                        <a
                            href="{{ route('sourcing-request.create') }}"
                            wire:navigate
                            @class([
                                'relative px-3 py-2 text-sm font-medium transition pb-[calc(0.5rem+2px)]',
                                'text-[var(--hs-neutral-900)] font-semibold after:absolute after:bottom-0 after:left-3 after:right-3 after:h-0.5 after:bg-[var(--hs-gold-500)] after:rounded-full' => request()->routeIs('sourcing-request.create'),
                                'text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)]' => ! request()->routeIs('sourcing-request.create'),
                            ])
                        >
                            {{ __('Sourcing') }}
                        </a>
                        <a
                            href="{{ route('about') }}"
                            wire:navigate
                            @class([
                                'relative px-3 py-2 text-sm font-medium transition pb-[calc(0.5rem+2px)]',
                                'text-[var(--hs-neutral-900)] font-semibold after:absolute after:bottom-0 after:left-3 after:right-3 after:h-0.5 after:bg-[var(--hs-gold-500)] after:rounded-full' => request()->routeIs('about'),
                                'text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)]' => ! request()->routeIs('about'),
                            ])
                        >
                            {{ __('About') }}
                        </a>
                    </nav>
                @endunless

                {{-- Locale Switcher --}}
                <div class="relative" x-data="{ openLocale: false }" @keydown.escape.window="openLocale = false" @click.outside="openLocale = false">
                    <button
                        @click="openLocale = !openLocale"
                        class="flex items-center gap-1 rounded-lg px-2 py-2 text-sm font-medium text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-100)] transition"
                    >
                        <svg class="size-4 text-[var(--hs-neutral-500)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" /></svg>
                        <span class="uppercase">{{ app()->getLocale() }}</span>
                    </button>
                    <div
                        x-show="openLocale"
                        x-transition.opacity.duration.150ms
                        class="absolute right-0 mt-1 w-40 origin-top-right rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-lg)] py-1 z-50"
                        style="display: none;"
                    >
                        @php
                            $localeOptions = collect(config('marketplace.portals', []))
                                ->unique('locale')
                                ->map(fn ($p) => ['locale' => $p['locale'], 'flag' => $p['flag'], 'display_name' => $p['display_name']]);
                        @endphp
                        @foreach($localeOptions as $option)
                            <form method="POST" action="{{ route('locale.switch') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $option['locale'] }}">
                                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm hover:bg-[var(--hs-neutral-50)] {{ app()->getLocale() === $option['locale'] ? 'text-[var(--hs-gold-600)] font-semibold' : 'text-[var(--hs-neutral-700)]' }}">
                                    <span>{{ $option['flag'] }}</span>
                                    <span>{{ $option['display_name'] }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>

                @auth
                    <livewire:shared.notification-bell />
                @endauth

                {{-- Auth --}}
                @auth
                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <button
                            @click="open = !open"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-100)] transition"
                        >
                            @if ($user->photo_url)
                                <img src="{{ $user->photo_url }}" alt="{{ __('Profile photo') }}" class="size-7 shrink-0 rounded-full object-cover" />
                            @else
                                <span class="inline-flex size-7 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-xs font-bold text-white shrink-0">
                                    {{ strtoupper(mb_substr($user->first_name, 0, 1) . mb_substr($user->last_name, 0, 1)) }}
                                </span>
                            @endif
                            <span class="hidden sm:block max-w-[120px] truncate">{{ $user->first_name }}</span>
                            <svg class="size-4 text-[var(--hs-neutral-400)] transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                            class="absolute right-0 mt-1 w-52 origin-top-right rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-lg)] py-1"
                            style="display: none;"
                        >
                            <div class="px-4 py-3 border-b border-[var(--hs-neutral-100)]">
                                <p class="text-sm font-semibold text-[var(--hs-neutral-900)] truncate">{{ $user->first_name }} {{ $user->last_name }}</p>
                                <p class="text-xs text-[var(--hs-neutral-500)] truncate mt-0.5">{{ $user->email }}</p>
                            </div>

                            <div class="py-1">
                                {{-- Single portal entry for all non-admin authenticated users --}}
                                @unless ($isAdmin)
                                    <a href="{{ route('portal.dashboard') }}" wire:navigate @click="open = false"
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-50)] transition">
                                        <svg class="size-4 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>
                                        {{ __('My Portal') }}
                                    </a>
                                @endunless

                                @if ($isAdmin)
                                    <a href="{{ route('admin.dashboard.index') }}" wire:navigate @click="open = false"
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-50)] transition">
                                        <svg class="size-4 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                        {{ __('Admin Console') }}
                                    </a>
                                @endif

                                <a href="{{ route('profile.edit') }}" wire:navigate @click="open = false"
                                   class="flex items-center gap-3 px-4 py-2 text-sm text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-50)] transition">
                                    <svg class="size-4 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    {{ __('Profile Settings') }}
                                </a>
                            </div>

                            <div class="border-t border-[var(--hs-neutral-100)] pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="flex w-full items-center gap-3 px-4 py-2 text-sm text-[var(--hs-danger-700)] hover:bg-[var(--hs-danger-50)] transition">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                                        {{ __('Log out') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" wire:navigate
                       class="px-3 py-2 text-sm font-medium text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-900)] transition">
                        {{ __('Log in') }}
                    </a>
                    <a href="{{ route('register') }}" wire:navigate
                       class="inline-flex items-center rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
                        {{ __('Get Started') }}
                    </a>
                @endauth

            </div>
        </div>
    </div>
</header>
