{{--
    Portal navigation — unified sidebar for all authenticated (non-admin) portal pages.

    Context-aware display rules:
    - Dashboard: always visible.
    - My RFQs + Messages (buyer): always visible for non-admin users.
    - Seller section (Shop Profile, Products, Subscription, Certification): only when
      the user's shop status is 'active' (i.e., approved / onboarded).
    - "Register Business" link: shown when user has no shop at all.
    - "Complete Onboarding" link: shown when a shop exists but is not yet approved.
    - Orders, Earnings, Payouts, Reviews: gated by their Phase 6 feature flags (off by default).
    - Admin portal keeps its own dedicated nav — never merged into this partial.
--}}
@php
    $portal ??= 'portal';
    $user = auth()->user();
    $shop = $user?->shop;
    $isOnboarded = $shop?->status === 'active';
    $isOnboarding = $shop && $shop->status === 'inactive';
    $isSuspended  = $shop?->status === 'suspended';
@endphp

@if ($portal === 'admin')
    {{-- ═══════════════════════════════════════════════════════════════
         ADMIN PORTAL (unchanged — separate navigation)
         ═══════════════════════════════════════════════════════════════ --}}
    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Admin Console') }}</h2>
    <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Review, verify and manage the directory.') }}</p>

    <nav class="mt-6 space-y-1">
        <a
            href="{{ route('admin.dashboard.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.dashboard.index'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.dashboard.index'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>
            {{ __('Dashboard') }}
        </a>

        <a
            href="{{ route('admin.onboardings.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.onboardings.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.onboardings.*'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
            {{ __('Onboarding Queue') }}
        </a>

        @if(config('marketplace.features.admin_rfq_management_enabled'))
            <a
                href="{{ route('admin.rfqs.index') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.rfqs.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.rfqs.*'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" /></svg>
                {{ __('Request for Quotations') }}
            </a>
        @endif

         <a
            href="{{ route('admin.sourcing-requests.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.sourcing-requests.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.sourcing-requests.*'),
            ])
            wire:navigate
        >
            <flux:icon name="magnifying-glass" class="size-5 shrink-0" />
            {{ __('Sourcing Requests') }}
        </a>

        <a
            href="{{ route('admin.merchants.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.merchants.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.merchants.*'),
            ])
            wire:navigate
        >
            <flux:icon name="building-storefront" class="size-5 shrink-0" />
            {{ __('Businesses') }}
        </a>

        <a
            href="{{ route('admin.certifications.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.certifications.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.certifications.*'),
            ])
            wire:navigate
        >
            <flux:icon name="check-badge" class="size-5 shrink-0" />
            {{ __('Halal Certifications') }}
        </a>

        <a
            href="{{ route('admin.subscriptions.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.subscriptions.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.subscriptions.*'),
            ])
            wire:navigate
        >
            <flux:icon name="credit-card" class="size-5 shrink-0" />
            {{ __('Subscriptions') }}
        </a>

        <a
            href="{{ route('admin.products.moderation') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.products.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.products.*'),
            ])
            wire:navigate
        >
            <flux:icon name="shield-exclamation" class="size-5 shrink-0" />
            {{ __('Product Moderation') }}
        </a>

        <a
            href="{{ route('admin.categories.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.categories.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.categories.*'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>
            {{ __('Product Categories') }}
        </a>

        @if(config('marketplace.features.sponsored_content_enabled'))
            <a
                href="{{ route('admin.sponsored-content.index') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.sponsored-content.index') || request()->routeIs('admin.sponsored-content.create') || request()->routeIs('admin.sponsored-content.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.sponsored-content.index') && !request()->routeIs('admin.sponsored-content.create') && !request()->routeIs('admin.sponsored-content.edit'),
                ])
                wire:navigate
            >
                <flux:icon name="megaphone" class="size-5 shrink-0" />
                {{ __('Sponsored Content') }}
            </a>
        @endif

        @if(config('marketplace.features.disputes_enabled'))
            <a
                href="{{ route('admin.disputes.index') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.disputes.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.disputes.*'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                {{ __('Disputes') }}
            </a>
        @endif

        @if(config('marketplace.features.reviews_enabled'))
            <a
                href="{{ route('admin.review-reports.index') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.review-reports.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.review-reports.*'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" /></svg>
                {{ __('Review Reports') }}
            </a>
        @endif

        {{-- Admin Settings --}}
        <div class="pt-4 mt-2 border-t border-[var(--hs-neutral-200)]">
            <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)]">{{ __('Settings') }}</p>
            @include('partials.portal-nav-settings')
        </div>
    </nav>

@else
    {{-- ═══════════════════════════════════════════════════════════════
         UNIFIED PORTAL NAVIGATION (Phase 6)
         Single sidebar for all authenticated non-admin users.
         Shows buyer items always; seller items conditioned on shop status.
         ═══════════════════════════════════════════════════════════════ --}}
    <h2 class="px-3 text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('My Portal') }}</h2>
    <p class="px-3 mt-1 text-sm text-[var(--hs-neutral-500)]">
        @if ($isOnboarded)
            {{ __('Manage your shop and activity.') }}
        @elseif ($isOnboarding)
            {{ __('Complete onboarding to go live.') }}
        @elseif ($isSuspended)
            {{ __('Your account has been suspended.') }}
        @else
            {{ __('Browse, source, and connect.') }}
        @endif
    </p>

    <nav class="mt-6 space-y-1">

        {{-- ─── Dashboard (always visible) ─────────────────────────────── --}}
        <a href="{{ route('portal.dashboard') }}" @class([
            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
            'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('portal.dashboard') || request()->routeIs('account.dashboard') || request()->routeIs('hub.hub-dashboard'),
            'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('portal.dashboard') && !request()->routeIs('account.dashboard') && !request()->routeIs('hub.hub-dashboard'),
        ]) wire:navigate>
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>
            {{ __('Dashboard') }}
        </a>

        {{-- ─── Seller section (approved/onboarded accounts only) ───────── --}}
        @if ($isOnboarded)
            <div class="pt-4 mt-2 border-t border-[var(--hs-neutral-200)]">
                <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)]">{{ __('Business') }}</p>

                <a href="{{ route('hub.shop.profile') }}" @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.shop.profile'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.shop.profile'),
                ]) wire:navigate>
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72" /></svg>
                    {{ __('Business Profile') }}
                </a>

                <a href="{{ route('hub.products.index') }}" @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.products.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.products.*'),
                ]) wire:navigate>
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                    {{ __('Products') }}
                </a>

                <a href="{{ route('hub.certifications.index') }}" @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.certifications.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.certifications.*'),
                ]) wire:navigate>
                    <flux:icon name="check-badge" class="size-5 shrink-0" />
                    {{ __('Halal Certification') }}
                </a>

                @if(config('marketplace.features.subscriptions_enabled'))
                    <a href="{{ route('hub.subscription') }}" @class([
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.subscription'),
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.subscription'),
                    ]) wire:navigate>
                        <flux:icon name="credit-card" class="size-5 shrink-0" />
                        {{ __('Subscription') }}
                    </a>
                @endif

                @if(config('marketplace.features.chat_enabled'))
                    <a href="{{ route('hub.messages') }}" @class([
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.messages'),
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.messages'),
                    ]) wire:navigate>
                        <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                        {{ __('Messages') }}
                        @if ($unreadCount = $user?->unreadMessagesCount())
                            <span class="ml-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[var(--hs-green-600)] px-1.5 text-[10px] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                @endif

                {{-- Phase 6 flagged-off seller features --}}
                @if(config('marketplace.features.seller_rfq_queue_enabled'))
                    <a href="{{ route('hub.rfqs.index') }}" @class([
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.rfqs.*') || request()->routeIs('hub.quotations.*'),
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.rfqs.*') && !request()->routeIs('hub.quotations.*'),
                    ]) wire:navigate>
                        <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" /></svg>
                        {{ __('RFQ Queue') }}
                    </a>
                @endif

                @if(config('marketplace.features.orders_enabled'))
                    <a href="{{ route('hub.orders.index') }}" @class([
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.orders.*'),
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.orders.*'),
                    ]) wire:navigate>
                        <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                        {{ __('Seller Orders') }}
                    </a>
                @endif

                @if(config('marketplace.features.reviews_enabled'))
                    <a href="{{ route('hub.reviews.index') }}" @class([
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.reviews.*'),
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.reviews.*'),
                    ]) wire:navigate>
                        <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                        {{ __('Reviews') }}
                    </a>
                @endif

                @if(config('marketplace.features.payouts_enabled'))
                    <a href="{{ route('hub.earnings') }}" @class([
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.earnings'),
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.earnings'),
                    ]) wire:navigate>
                        <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        {{ __('Earnings & Payouts') }}
                    </a>
                    <a href="{{ route('hub.payout-account') }}" @class([
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.payout-account'),
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.payout-account'),
                    ]) wire:navigate>
                        <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" /></svg>
                        {{ __('Payout Account') }}
                    </a>
                @endif

            </div>

        @elseif ($isOnboarding)
            {{-- ─── Onboarding in progress ───────────────────────────────── --}}
            <a href="{{ route('account.onboarding') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.onboarding'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.onboarding'),
            ]) wire:navigate>
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
                {{ __('Complete Onboarding') }}
            </a>

        @else
            {{-- ─── Buyer-only (no shop) — prompt to Register Business ─────────── --}}
            @unless ($isSuspended)
            <a href="{{ route('account.onboarding') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.onboarding'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.onboarding'),
            ]) wire:navigate>
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" /></svg>
                {{ __('Register Business') }}
            </a>
            @endunless
        @endif

        {{-- ─── Buyer section (always visible for non-admin users) ─────── --}}
        <div class="pt-4 mt-2 border-t border-[var(--hs-neutral-200)]">
            <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)]">{{ __('Buying') }}</p>

            <a href="{{ route('account.profile') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.profile'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.profile'),
            ]) wire:navigate>
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" /></svg>
                {{ __('Buyer Profile') }}
            </a>

            <a href="{{ route('account.rfqs.index') }}" @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.rfqs.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.rfqs.*'),
            ]) wire:navigate>
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                {{ __('My Request for Quotation') }}
            </a>

            @if(config('marketplace.features.chat_enabled'))
                <a href="{{ route('account.messages') }}" @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.messages'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.messages'),
                ]) wire:navigate>
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                    {{ __('Messages') }}
                    @if ($unreadCount = $user?->unreadMessagesCount())
                        <span class="ml-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[var(--hs-green-600)] px-1.5 text-[10px] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </a>
            @endif

            @if(config('marketplace.features.orders_enabled'))
                <a href="{{ route('account.orders.index') }}" @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.orders.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.orders.*'),
                ]) wire:navigate>
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    {{ __('My Orders') }}
                </a>
                <a href="{{ route('account.payments') }}" @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition border border-transparent',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.payments'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.payments'),
                ]) wire:navigate>
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                    {{ __('Payment History') }}
                </a>
            @endif
        </div>

        {{-- ─── Settings (always visible) ─────────────────────────────── --}}
        <div class="pt-4 mt-2 border-t border-[var(--hs-neutral-200)]">
            <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)]">{{ __('Settings') }}</p>
            @include('partials.portal-nav-settings')
        </div>

    </nav>
@endif
