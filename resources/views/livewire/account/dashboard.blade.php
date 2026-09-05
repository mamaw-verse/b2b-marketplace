<div class="space-y-6">

    {{-- Welcome / Start Selling CTA --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Welcome back, :name', ['name' => auth()->user()->first_name]) }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Track your orders and purchases at a glance.') }}</p>
        </div>
        @unless ($this->hasShop)
            <a href="{{ route('account.onboarding') }}" wire:navigate
               class="inline-flex items-center justify-center rounded-[var(--hs-radius-pill)] bg-[var(--hs-green-600)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--hs-green-700)] transition shadow-[var(--hs-shadow-sm)]">
                {{ __('Start Selling') }}
            </a>
        @endunless
    </div>

    {{-- Status cards: To Pay / To Ship / To Receive / Completed --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $cards = [
                ['label' => __('To Pay'), 'key' => 'to_pay', 'tab' => 'to_pay'],
                ['label' => __('To Ship'), 'key' => 'to_ship', 'tab' => 'to_ship'],
                ['label' => __('To Receive'), 'key' => 'to_receive', 'tab' => 'to_receive'],
                ['label' => __('Completed'), 'key' => 'completed', 'tab' => 'completed'],
            ];
        @endphp
        @foreach ($cards as $card)
            <a href="{{ route('account.orders.index', ['tab' => $card['tab']]) }}" wire:navigate
               class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] p-4 text-center hover:border-[var(--hs-green-300)] transition">
                <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">{{ $this->statusCounts[$card['key']] }}</p>
                <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ $card['label'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- Recent orders --}}
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--hs-neutral-100)]">
            <h2 class="text-base font-semibold text-[var(--hs-neutral-900)]">{{ __('Recent Orders') }}</h2>
            <a href="{{ route('account.orders.index') }}" wire:navigate class="text-sm font-medium text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)]">
                {{ __('View all') }}
            </a>
        </div>

        <div class="divide-y divide-[var(--hs-neutral-100)]">
            @forelse ($this->recentOrders as $order)
                <a href="{{ route('account.orders.show', $order) }}" wire:navigate
                   class="flex items-center justify-between px-6 py-4 hover:bg-[var(--hs-neutral-50)] transition">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-[var(--hs-neutral-900)] truncate">{{ $order->shop?->storefront_name ?? __('Order') }}</p>
                        <p class="text-sm text-[var(--hs-neutral-500)] mt-0.5">{{ $order->order_number }} &middot; {{ $order->created_at->format('M d, Y') }}</p>
                    </div>
                    <div class="text-right shrink-0 ml-4">
                        <p class="font-semibold text-[var(--hs-neutral-900)]">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
                        <p class="text-xs text-[var(--hs-neutral-500)] mt-0.5 capitalize">{{ str_replace('_', ' ', $order->status) }}</p>
                    </div>
                </a>
            @empty
                <div class="px-6 py-16 text-center text-sm text-[var(--hs-neutral-500)]">
                    {{ __('You have no orders yet.') }}
                    <a href="{{ route('storefront.products') }}" wire:navigate class="text-[var(--hs-green-700)] font-medium hover:underline">{{ __('Browse products') }}</a>
                </div>
            @endforelse
        </div>
    </div>
</div>
