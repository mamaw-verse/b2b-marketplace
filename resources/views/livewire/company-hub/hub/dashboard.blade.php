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
            @foreach ([
                ['label' => 'New RFQs', 'count' => $counts['new_rfqs'] ?? 0, 'route' => 'hub.rfqs.index', 'color' => 'green'],
                ['label' => 'Orders In Progress', 'count' => $counts['orders_in_progress'] ?? 0, 'route' => 'hub.orders.index', 'color' => 'blue'],
                ['label' => 'Unfulfilled', 'count' => $counts['unfulfilled_orders'] ?? 0, 'route' => 'hub.orders.index', 'color' => 'warning'],
                ['label' => 'Draft Orders', 'count' => $counts['draft_orders'] ?? 0, 'route' => 'hub.orders.index', 'color' => 'neutral'],
                ['label' => 'Open Disputes', 'count' => $counts['open_disputes'] ?? 0, 'route' => 'hub.orders.index', 'color' => 'red'],
            ] as $card)
                <a href="{{ route($card['route']) }}" wire:navigate class="block p-4 rounded-lg border border-[var(--hs-neutral-200)] hover:border-[var(--hs-{{ $card['color'] }}-300)] transition">
                    <p class="text-3xl font-bold text-[var(--hs-neutral-900)]">{{ $card['count'] }}</p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">{{ $card['label'] }}</p>
                </a>
            @endforeach
        </div>
    </div>
</div>
