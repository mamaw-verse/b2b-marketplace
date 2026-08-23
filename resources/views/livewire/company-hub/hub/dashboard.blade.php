<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Seller Hub</h1>
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
