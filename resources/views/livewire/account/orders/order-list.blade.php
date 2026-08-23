<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">My Orders</h1>
        </div>

        <div class="px-6 pt-5">
            <div class="flex gap-1.5 bg-[var(--hs-neutral-100)] rounded-[var(--hs-radius-pill)] p-1 w-fit flex-wrap">
                @foreach (['to_pay' => 'To Pay', 'to_ship' => 'To Ship', 'to_receive' => 'To Receive', 'completed' => 'Completed', 'disputed' => 'Disputes'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')" @class(['px-4 py-1.5 text-sm font-medium rounded-[var(--hs-radius-pill)] transition', 'bg-[var(--hs-neutral-0)] text-[var(--hs-green-700)] shadow-sm' => $tab === $key, 'text-[var(--hs-neutral-600)] hover:text-[var(--hs-neutral-800)]' => $tab !== $key])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="p-6">
            @if ($this->orders->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">No orders in this tab.</p>
            @else
                <div class="space-y-3">
                    @foreach ($this->orders as $order)
                        <a href="{{ route('account.orders.show', $order) }}" wire:navigate class="block p-4 rounded-lg border border-[var(--hs-neutral-200)] hover:border-[var(--hs-green-300)] transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-[var(--hs-neutral-900)]">{{ $order->shop->storefront_name }}</p>
                                    <p class="text-sm text-[var(--hs-neutral-500)] mt-0.5">Order #{{ $order->id }} &middot; {{ $order->created_at->format('M d, Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-[var(--hs-neutral-900)]">{{ $order->currency }} {{ number_format((float) $order->order_total, 2) }}</p>
                                    <x-hs-status-pill :status="$order->status" class="mt-1" />
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-4">{{ $this->orders->links() }}</div>
            @endif
        </div>
    </div>
</div>
