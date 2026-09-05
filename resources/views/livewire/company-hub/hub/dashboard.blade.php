<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Seller Hub</h1>
            @if ($this->shop && $this->shop->status === 'active')
                <a href="{{ route('storefront.company', $this->shop) }}" wire:navigate
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] transition">
                    {{ __('View My Shop') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                </a>
            @endif
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @php
                $cards = [];

                if (config('marketplace.features.seller_rfq_queue_enabled') || config('marketplace.features.admin_rfq_management_enabled')) {
                    $cards[] = ['label' => 'New RFQs', 'count' => $counts['new_rfqs'] ?? 0, 'route' => 'hub.rfqs.index', 'color' => 'green'];
                }
                
                if (config('marketplace.features.orders_enabled')) {
                    $cards[] = ['label' => 'Orders In Progress', 'count' => $counts['orders_in_progress'] ?? 0, 'route' => 'hub.orders.index', 'color' => 'blue'];
                    $cards[] = ['label' => 'Unfulfilled', 'count' => $counts['unfulfilled_orders'] ?? 0, 'route' => 'hub.orders.index', 'color' => 'warning'];
                    $cards[] = ['label' => 'Draft Orders', 'count' => $counts['draft_orders'] ?? 0, 'route' => 'hub.orders.index', 'color' => 'neutral'];
                }
                
                if (config('marketplace.features.disputes_enabled')) {
                    $cards[] = ['label' => 'Open Disputes', 'count' => $counts['open_disputes'] ?? 0, 'route' => 'hub.orders.index', 'color' => 'red'];
                }
            @endphp
            @foreach ($cards as $card)
                <a href="{{ route($card['route']) }}" wire:navigate class="block p-4 rounded-lg border border-[var(--hs-neutral-200)] hover:border-[var(--hs-{{ $card['color'] }}-300)] transition">
                    <p class="text-3xl font-bold text-[var(--hs-neutral-900)]">{{ $card['count'] }}</p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">{{ $card['label'] }}</p>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Shop Status Metrics (M8) --}}
    <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
        @if(config('marketplace.features.subscriptions_enabled') && isset($counts['subscription']))
            <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5">
                <h2 class="font-semibold text-[var(--hs-neutral-900)]">Subscription</h2>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Plan: <span class="font-medium text-[var(--hs-neutral-900)]">{{ $counts['subscription']['plan_name'] ?? 'None' }}</span></p>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Status: <span class="font-medium text-[var(--hs-neutral-900)] capitalize">{{ $counts['subscription']['status'] ?? 'Unknown' }}</span></p>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Renewal: <span class="font-medium text-[var(--hs-neutral-900)]">{{ isset($counts['subscription']['renewal_date']) ? \Carbon\Carbon::parse($counts['subscription']['renewal_date'])->format('M j, Y') : 'N/A' }}</span></p>
            </div>
        @endif

        <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5">
            <h2 class="font-semibold text-[var(--hs-neutral-900)]">Listings</h2>
            <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Published: <span class="font-medium text-[var(--hs-neutral-900)]">{{ $counts['listings']['published'] ?? 0 }}</span></p>
            <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Draft: <span class="font-medium text-[var(--hs-neutral-900)]">{{ $counts['listings']['draft'] ?? 0 }}</span></p>
            <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Flagged/Unpublished: <span class="font-medium text-[var(--hs-neutral-900)]">{{ ($counts['listings']['flagged'] ?? 0) + ($counts['listings']['unpublished'] ?? 0) }}</span></p>
        </div>

        <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5">
            <h2 class="font-semibold text-[var(--hs-neutral-900)]">Certification</h2>
            @if(($counts['certification']['is_certified'] ?? false) == true)
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Status: <span class="font-medium text-[var(--hs-green-700)]">Certified</span></p>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Expiry: <span class="font-medium text-[var(--hs-neutral-900)]">{{ isset($counts['certification']['expiry_date']) ? \Carbon\Carbon::parse($counts['certification']['expiry_date'])->format('M j, Y') : 'N/A' }}</span></p>
            @else
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">Status: <span class="font-medium text-[var(--hs-neutral-900)]">Not Certified</span></p>
            @endif
        </div>
    </div>

    @if ($this->shop && ! $this->certificationApplication)
        <div class="mt-4 flex flex-col gap-4 rounded-[var(--hs-radius-card)] border border-[var(--hs-green-200)] bg-[var(--hs-green-50)] p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-[var(--hs-neutral-900)]">Interested in halal certification?</h2>
                <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">Let us know and we will connect you with the appropriate external certification entity.</p>
            </div>
            <flux:button href="{{ route('hub.certifications.index') }}" variant="primary" wire:navigate>
                I'm Interested
            </flux:button>
        </div>
    @elseif ($this->certificationApplication)
        <div class="mt-4 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5">
            <p class="text-sm text-[var(--hs-neutral-700)]">Halal certification interest: <span class="font-semibold">{{ str($this->certificationApplication->status)->headline() }}</span></p>
        </div>
    @endif
</div>
