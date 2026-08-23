{{-- Portal navigation — used by both the desktop sidebar and the mobile drawer in layouts/portal.blade.php --}}
@php
    $portal ??= 'hub';
@endphp

@if ($portal === 'hub')
    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Company Hub') }}</h2>
    <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
        @php
            $shop = auth()->user()?->shop;
            $statusLine = match ($shop?->status ?? null) {
                'active' => 'Your company is live on the directory.',
                'pending' => 'Onboarding in progress.',
                'suspended' => 'Your account is suspended.',
                default => 'Complete onboarding to go live.',
            };
        @endphp
        {{ $statusLine }}
    </p>
@elseif ($portal === 'admin')
    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Admin Console') }}</h2>
    <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Review, verify and manage the directory.') }}</p>
@elseif ($portal === 'account')
    <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('My Account') }}</h2>
    <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Manage your buyer profile and RFQs.') }}</p>
@endif

<nav class="mt-6 space-y-1">
    @if ($portal === 'hub')
        @if (auth()->user()?->shop?->status !== 'active')
            {{-- Onboarding Wizard --}}
            <a
                href="{{ route('hub.onboarding') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.onboarding'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.onboarding'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
                {{ __('Onboarding Wizard') }}
            </a>
        @endif

        @if (auth()->user()?->shop?->status === 'active')
            {{-- Dashboard --}}
            <a
                href="{{ route('hub.hub-dashboard') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.hub-dashboard'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.hub-dashboard'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
                </svg>
                {{ __('Dashboard') }}
            </a>

            {{-- Products --}}
            <a
                href="{{ route('hub.products.index') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.products.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.products.*'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                {{ __('Products') }}
            </a>

            {{-- RFQ Queue --}}
            <a
                href="{{ route('hub.rfqs.index') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.rfqs.*') || request()->routeIs('hub.quotations.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.rfqs.*') && !request()->routeIs('hub.quotations.*'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" />
                </svg>
                {{ __('RFQ Queue') }}
            </a>

            {{-- Orders --}}
            <a
                href="{{ route('hub.orders.index') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.orders.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.orders.*'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                {{ __('Orders') }}
            </a>

            {{-- Seller Messages --}}
            <a
                href="{{ route('hub.messages') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('hub.messages'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('hub.messages'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                </svg>
                {{ __('Messages') }}
            </a>
        @endif

        {{-- Buyer Account section --}}
        <div class="pt-4 mt-2 border-t border-[var(--hs-neutral-200)]">
            <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)]">{{ __('Buyer Account') }}</p>

            <a
                href="{{ route('account.profile') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.profile'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.profile'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
                {{ __('Buyer Profile') }}
            </a>

            <a
                href="{{ route('account.rfqs.index') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.rfqs.*'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.rfqs.*'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                {{ __('My RFQs') }}
            </a>

            @if (auth()->user()?->shop?->status !== 'active')
                {{-- Buyer Messages --}}
                <a
                    href="{{ route('account.messages') }}"
                    @class([
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                        'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.messages'),
                        'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.messages'),
                    ])
                    wire:navigate
                >
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                    {{ __('Messages') }}
                </a>
            @endif
        </div>

        {{-- Settings section --}}
        <div class="pt-4 mt-2 border-t border-[var(--hs-neutral-200)]">
            <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)]">{{ __('Settings') }}</p>

            <a
                href="{{ route('profile.edit') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('profile.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('profile.edit'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                {{ __('Profile') }}
            </a>

            <a
                href="{{ route('security.edit') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('security.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('security.edit'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                {{ __('Security') }}
            </a>

            <a
                href="{{ route('appearance.edit') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('appearance.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('appearance.edit'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                </svg>
                {{ __('Appearance') }}
            </a>
        </div>

    @elseif ($portal === 'admin')

        <a
            href="{{ route('admin.onboardings.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.onboardings.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.onboardings.*'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" />
            </svg>
            {{ __('Onboarding Queue') }}
        </a>

        <a
            href="{{ route('admin.categories.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.categories.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.categories.*'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
            </svg>
            {{ __('Categories') }}
        </a>

        <a
            href="{{ route('admin.disputes.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('admin.disputes.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('admin.disputes.*'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            {{ __('Disputes') }}
        </a>

        <div class="pt-4 mt-2 border-t border-[var(--hs-neutral-200)]">
            <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)]">{{ __('Settings') }}</p>

            <a
                href="{{ route('profile.edit') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('profile.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('profile.edit'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                {{ __('Profile') }}
            </a>

            <a
                href="{{ route('security.edit') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('security.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('security.edit'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                {{ __('Security') }}
            </a>

            <a
                href="{{ route('appearance.edit') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('appearance.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('appearance.edit'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                </svg>
                {{ __('Appearance') }}
            </a>
            </div>

    @elseif ($portal === 'account')

        {{-- Buyer Account --}}
        <a
            href="{{ route('account.profile') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.profile'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.profile'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
            </svg>
            {{ __('Buyer Profile') }}
        </a>

        <a
            href="{{ route('account.rfqs.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.rfqs.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.rfqs.*'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            {{ __('My RFQs') }}
        </a>

        <a
            href="{{ route('account.orders.index') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.orders.*'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.orders.*'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
            {{ __('My Orders') }}
        </a>

        <a
            href="{{ route('account.messages') }}"
            @class([
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('account.messages'),
                'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('account.messages'),
            ])
            wire:navigate
        >
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
            </svg>
            {{ __('Messages') }}
        </a>

        {{-- Settings section --}}
        <div class="pt-4 mt-2 border-t border-[var(--hs-neutral-200)]">
            <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)]">{{ __('Settings') }}</p>

            <a
                href="{{ route('profile.edit') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('profile.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('profile.edit'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                {{ __('Profile') }}
            </a>

            <a
                href="{{ route('security.edit') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('security.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('security.edit'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                {{ __('Security') }}
            </a>

            <a
                href="{{ route('appearance.edit') }}"
                @class([
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                    'bg-[var(--hs-green-50)] text-[var(--hs-green-700)] border border-[var(--hs-green-200)]' => request()->routeIs('appearance.edit'),
                    'text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)]' => !request()->routeIs('appearance.edit'),
                ])
                wire:navigate
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                </svg>
                {{ __('Appearance') }}
            </a>
        </div>

    @endif
</nav>
