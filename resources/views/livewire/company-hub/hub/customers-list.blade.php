<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Customers') }}</h1>
        </div>
        <div class="p-4 sm:p-6">
            @if ($this->customers->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">{{ __('No customers yet.') }}</p>
            @else
                {{-- Mobile card list (hidden on sm+) --}}
                <div class="sm:hidden space-y-3">
                    @foreach ($this->customers as $customer)
                        <div class="flex items-center justify-between gap-3 rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] p-4">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-[var(--hs-neutral-900)] truncate">{{ $customer->first_name }} {{ $customer->last_name }}</p>
                                <p class="text-sm text-[var(--hs-neutral-500)] truncate mt-0.5">{{ $customer->email }}</p>
                                <p class="text-xs text-[var(--hs-neutral-400)] mt-1">
                                    {{ trans_choice(':count order|:count orders', $customer->orders_count, ['count' => $customer->orders_count]) }}
                                </p>
                            </div>
                            <flux:button size="sm" variant="ghost" href="{{ route('hub.orders.create', ['buyerUserId' => $customer->id]) }}" wire:navigate>
                                {{ __('New Order') }}
                            </flux:button>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop table (hidden on mobile) --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--hs-neutral-100)]">
                                <th class="text-left py-3 text-[var(--hs-neutral-600)]">{{ __('Name') }}</th>
                                <th class="text-left py-3 text-[var(--hs-neutral-600)]">{{ __('Email') }}</th>
                                <th class="text-right py-3 text-[var(--hs-neutral-600)]">{{ __('Orders') }}</th>
                                <th class="py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->customers as $customer)
                                <tr class="border-b border-[var(--hs-neutral-50)]">
                                    <td class="py-3 font-medium text-[var(--hs-neutral-900)]">{{ $customer->first_name }} {{ $customer->last_name }}</td>
                                    <td class="py-3 text-[var(--hs-neutral-700)]">{{ $customer->email }}</td>
                                    <td class="py-3 text-right text-[var(--hs-neutral-900)]">{{ $customer->orders_count }}</td>
                                    <td class="py-3 text-right">
                                        <flux:button size="sm" variant="ghost" href="{{ route('hub.orders.create', ['buyerUserId' => $customer->id]) }}" wire:navigate>{{ __('New Order') }}</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $this->customers->links() }}</div>
            @endif
        </div>
    </div>
</div>
