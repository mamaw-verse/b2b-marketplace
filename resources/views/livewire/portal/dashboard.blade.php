<div class="space-y-6">

    {{-- ── Welcome banner ──────────────────────────────────────────────────── --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Welcome back, :name', ['name' => auth()->user()->first_name]) }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                @if ($this->isOnboarded)
                    {{ __('Manage your shop, listings, and activity.') }}
                @else
                    {{ __('Browse, source, and connect with verified halal suppliers.') }}
                @endif
            </p>
        </div>
        @if ($this->isOnboarded && $this->shop)
            <a href="{{ route('storefront.company', $this->shop) }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition shrink-0">
                {{ __('View Business') }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
            </a>
        @elseif (! $this->shop)
            <a href="{{ route('account.onboarding') }}" wire:navigate
               class="inline-flex items-center justify-center rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)] shrink-0">
                {{ __('Register Business') }}
            </a>
        @endif
    </div>

    {{-- ── SELLER SECTION — only visible for approved merchants ────────────── --}}
    @if ($this->isOnboarded)

        {{-- Subscription status banner --}}
        @if (config('marketplace.features.subscriptions_enabled'))
            @if ($this->activeSubscription)
                @php
                    $sub = $this->activeSubscription;
                    $isTrialing = $sub->status === 'trialing';
                    $expiryDate = $isTrialing ? $sub->trial_ends_at : $sub->current_period_end;
                @endphp
                <div class="flex items-center justify-between gap-4 rounded-[var(--hs-radius-card)] border border-[var(--hs-success-200)] bg-[var(--hs-success-50)] px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex size-8 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-white">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-[var(--hs-success-700)]">
                                {{ $isTrialing ? __('Free Trial Active') : __('Subscription Active') }}
                            </p>
                            @if ($expiryDate)
                                <p class="text-xs text-[var(--hs-neutral-600)] mt-0.5">
                                    {{ $isTrialing ? __('Trial ends') : __('Renews') }} {{ $expiryDate->format('M d, Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('hub.subscription') }}" wire:navigate class="text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] shrink-0">
                        {{ __('Manage') }}
                    </a>
                </div>
            @else
                <div class="flex flex-col gap-4 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-[var(--hs-neutral-900)]">{{ __('No active subscription') }}</p>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">{{ __('Subscribe to publish your products and appear in search.') }}</p>
                    </div>
                    <flux:button href="{{ route('hub.subscription') }}" variant="primary" wire:navigate class="shrink-0">
                        {{ __('Choose a Plan') }}
                    </flux:button>
                </div>
            @endif
        @endif

        {{-- Seller quick-stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">

            {{-- Published products --}}
            <a href="{{ route('hub.products.index') }}" wire:navigate
               class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-4 text-center hover:border-[var(--hs-green-300)] transition">
                <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">{{ $this->sellerStats['published_products'] }}</p>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Published Products') }}</p>
            </a>

            {{-- My RFQs (count) --}}
            <a href="{{ route('account.rfqs.index') }}" wire:navigate
               class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-4 text-center hover:border-[var(--hs-green-300)] transition">
                <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">{{ $this->buyerRfqCount }}</p>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('My Request for Quotations') }}</p>
            </a>

            {{-- Halal certificates — uses bestCertificate priority: active > expiring_soon > under_review > expired --}}
            @php
                $bestCert = $this->bestCertificate;
                $certIsManageable = $bestCert && in_array($bestCert->status, ['active', 'expiring_soon', 'under_review'], true);
            @endphp
            <a href="{{ $certIsManageable ? route('hub.certifications.index') : route('apply.certificate') }}" wire:navigate
               class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-4 text-center hover:border-[var(--hs-green-300)] transition col-span-2 sm:col-span-1">
                @if ($certIsManageable)
                    <div class="flex items-center justify-center">
                        <x-hs-status-pill :status="$bestCert->status" />
                    </div>
                @else
                    <p class="text-2xl font-bold text-[var(--hs-neutral-400)]">—</p>
                @endif
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Halal Certification') }}</p>
            </a>
        </div>

        {{-- Certification CTA (when no certificates and no pending application) --}}
        @if ($this->bestCertificate === null && ! $this->certificationApplication)
            <div class="flex flex-col gap-4 rounded-[var(--hs-radius-card)] border border-[var(--hs-green-200)] bg-[var(--hs-green-50)] p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-[var(--hs-neutral-900)]">{{ __('Apply for Halal Certification') }}</h2>
                    <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">{{ __('Boost buyer trust with a verified halal certification badge on your shop.') }}</p>
                </div>
                <flux:button href="{{ route('apply.certificate') }}" variant="primary" wire:navigate class="shrink-0">
                    {{ __('Apply Now') }}
                </flux:button>
            </div>
        @elseif ($this->certificationApplication && $this->bestCertificate === null)
            {{-- Application in progress, no live cert yet --}}
            <div class="flex items-center gap-4 rounded-[var(--hs-radius-card)] border border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] px-5 py-4">
                <svg class="size-5 shrink-0 text-[var(--hs-warning-700)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <p class="text-sm text-[var(--hs-neutral-700)]">
                    {{ __('Your certification application is') }}
                    <span class="font-semibold">{{ str($this->certificationApplication->status)->headline() }}</span>.
                </p>
            </div>
        @endif

    @endif
    {{-- /SELLER SECTION --}}

    {{-- ── My RFQs ──────────────────────────────────────────────────────────── --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--hs-neutral-100)]">
            <h2 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('My Request for Quotation') }}</h2>
            <a href="{{ route('account.rfqs.index') }}" wire:navigate class="text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)]">
                {{ __('View all') }}
            </a>
        </div>

        @if ($this->recentRfqs->isEmpty())
            <div class="px-6 py-10 text-center">
                <p class="text-sm text-[var(--hs-neutral-500)]">
                    {{ __('No RFQs yet.') }}
                    <a href="{{ route('storefront.products') }}" wire:navigate class="text-[var(--hs-green-700)] font-medium hover:underline ml-1">{{ __('Browse products') }}</a>
                </p>
            </div>
        @else
            <div class="divide-y divide-[var(--hs-neutral-100)]">
                @foreach ($this->recentRfqs as $rfq)
                    <a href="{{ route('account.rfqs.show', $rfq) }}" wire:navigate
                       class="group flex items-center gap-3 px-6 py-4 hover:bg-[var(--hs-neutral-50)] transition">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <p class="truncate text-sm font-medium text-[var(--hs-neutral-900)]">{{ $rfq->title }}</p>
                                    <span class="shrink-0 text-xs text-[var(--hs-neutral-400)]">{{ $rfq->created_at->diffForHumans() }}</span>
                                </div>
                                <x-hs-status-pill :status="$rfq->recipients->first()?->admin_status ?? 'pending'" class="shrink-0" />
                            </div>
                        </div>
                        <flux:icon name="chevron-right" variant="mini" class="shrink-0 text-[var(--hs-neutral-300)] group-hover:text-[var(--hs-neutral-400)] transition" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Quick links ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <a href="{{ route('storefront.companies') }}" wire:navigate
           class="flex flex-col items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4 text-center hover:border-[var(--hs-green-300)] shadow-[var(--hs-shadow-sm)] transition">
            <svg class="size-6 text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
            <span class="text-sm font-medium text-[var(--hs-neutral-700)]">{{ __('Browse Businesses') }}</span>
        </a>
        <a href="{{ route('storefront.products') }}" wire:navigate
           class="flex flex-col items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4 text-center hover:border-[var(--hs-green-300)] shadow-[var(--hs-shadow-sm)] transition">
            <svg class="size-6 text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
            <span class="text-sm font-medium text-[var(--hs-neutral-700)]">{{ __('Browse Products') }}</span>
        </a>
        <a href="{{ route('account.rfqs.create') }}" wire:navigate
           class="flex flex-col items-center gap-2 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-4 text-center hover:border-[var(--hs-green-300)] shadow-[var(--hs-shadow-sm)] transition col-span-2 sm:col-span-1">
            <svg class="size-6 text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            <span class="text-sm font-medium text-[var(--hs-neutral-700)]">{{ __('Submit a Request for Quotation') }}</span>
        </a>
    </div>

</div>
