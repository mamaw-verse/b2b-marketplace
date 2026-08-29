{{--
    Portal mobile navigation — slide-in drawer for small screens (lg:hidden).
    Triggered by the hamburger button in portal-header.blade.php via a custom
    Alpine event ('toggle-portal-nav'). No x-teleport — fixed positioning inline.
    Receives $portal variable from the portal layout.
--}}
@php
    $user = auth()->user();
    $shop = $user?->shop;
    $isOnboarded = $shop?->status === 'active';
@endphp

<div
    x-data="{ mobileNavOpen: false }"
    @toggle-portal-nav.window="mobileNavOpen = !mobileNavOpen"
    @keydown.escape.window="mobileNavOpen = false"
    class="lg:hidden"
>
    {{-- Scrim (backdrop overlay) --}}
    <div
        x-show="mobileNavOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileNavOpen = false"
        class="fixed inset-0 z-[60] bg-[var(--hs-neutral-900)]/45 backdrop-blur-[2px]"
        style="display: none;"
        aria-hidden="true"
    ></div>

    {{-- Drawer panel --}}
    <div
        x-show="mobileNavOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-[70] flex w-[85%] max-w-[340px] flex-col bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-xl)]"
        style="display: none;"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('Navigation menu') }}"
    >
        {{-- Drawer header --}}
        <div class="flex items-center justify-between border-b border-[var(--hs-neutral-200)] px-5 py-4">
            <div class="min-w-0">
                @if (($portal ?? 'hub') === 'account')
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--hs-gold-700)]">{{ __('Buyer Hub') }}</p>
                    <p class="mt-0.5 truncate text-base font-bold text-[var(--hs-green-600)]">{{ trim($user->first_name . ' ' . $user->last_name) }}</p>
                @elseif ($isOnboarded)
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--hs-gold-700)]">{{ __('Seller Hub') }}</p>
                    <p class="mt-0.5 truncate text-base font-bold text-[var(--hs-green-600)]">{{ $shop->storefront_name }}</p>
                @else
                    <p class="text-xs font-bold uppercase tracking-wider text-[var(--hs-gold-700)]">{{ __('Seller Hub') }}</p>
                    <p class="mt-0.5 truncate text-base font-bold text-[var(--hs-green-600)]">{{ __('Company Hub') }}</p>
                @endif
            </div>
            <button
                @click="mobileNavOpen = false"
                class="flex size-8 items-center justify-center rounded-full bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-500)] transition hover:bg-[var(--hs-neutral-200)]"
                aria-label="{{ __('Close navigation menu') }}"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- Scrollable nav content --}}
        <div class="flex-1 overflow-y-auto overscroll-contain px-3 py-4" @click="mobileNavOpen = false">
            @include('partials.portal-nav', ['portal' => $portal])
        </div>

        {{-- Drawer footer — user info + sign out --}}
        <div class="flex items-center gap-3 border-t border-[var(--hs-neutral-200)] px-5 py-4">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-xs font-bold text-white">
                {{ strtoupper(mb_substr($user->first_name, 0, 1) . mb_substr($user->last_name, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-[var(--hs-neutral-900)]">{{ $user->first_name }} {{ $user->last_name }}</p>
                <p class="truncate text-xs text-[var(--hs-neutral-500)]">{{ $user->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="flex size-8 items-center justify-center rounded-full bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-500)] transition hover:bg-[var(--hs-danger-50)] hover:text-[var(--hs-danger-700)]"
                    aria-label="{{ __('Log out') }}"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                </button>
            </form>
        </div>
    </div>
</div>
