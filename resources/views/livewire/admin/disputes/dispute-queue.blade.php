<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Escalated Disputes') }}</h1>
        </div>
        <div class="p-6">
            @if ($this->disputes->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">{{ __('No escalated disputes.') }}</p>
            @else
                {{-- Mobile: stacked card list --}}
                <div class="lg:hidden divide-y divide-[var(--hs-neutral-100)]">
                    @foreach ($this->disputes as $dispute)
                        <div class="py-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-medium text-[var(--hs-neutral-900)]">#{{ $dispute->order_id }}</p>
                                <flux:button size="sm" href="{{ route('admin.disputes.show', $dispute) }}" wire:navigate>{{ __('Review') }}</flux:button>
                            </div>
                            <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                <div>
                                    <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Buyer') }}</dt>
                                    <dd class="text-[var(--hs-neutral-700)]">{{ $dispute->openedBy->first_name }} {{ $dispute->openedBy->last_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Shop') }}</dt>
                                    <dd class="text-[var(--hs-neutral-700)]">{{ $dispute->order->shop->storefront_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Type') }}</dt>
                                    <dd class="text-[var(--hs-neutral-700)]">{{ str_replace('_', ' ', ucfirst($dispute->type)) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-[var(--hs-neutral-500)]">{{ __('Escalated') }}</dt>
                                    <dd class="text-[var(--hs-neutral-500)]">{{ $dispute->escalated_at?->diffForHumans() }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop: table --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-[var(--hs-neutral-100)]">
                            <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Order') }}</th>
                            <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Buyer') }}</th>
                            <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Shop') }}</th>
                            <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Type') }}</th>
                            <th class="text-left py-3 px-2 text-[var(--hs-neutral-600)]">{{ __('Escalated') }}</th>
                            <th class="py-3 px-2"></th>
                        </tr></thead>
                        <tbody>
                            @foreach ($this->disputes as $dispute)
                                <tr class="border-b border-[var(--hs-neutral-50)]">
                                    <td class="py-3 px-2 font-medium text-[var(--hs-neutral-900)]">#{{ $dispute->order_id }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">{{ $dispute->openedBy->first_name }} {{ $dispute->openedBy->last_name }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-700)]">{{ $dispute->order->shop->storefront_name }}</td>
                                    <td class="py-3 px-2">{{ str_replace('_', ' ', ucfirst($dispute->type)) }}</td>
                                    <td class="py-3 px-2 text-[var(--hs-neutral-500)]">{{ $dispute->escalated_at?->diffForHumans() }}</td>
                                    <td class="py-3 px-2 text-right">
                                        <flux:button size="sm" href="{{ route('admin.disputes.show', $dispute) }}" wire:navigate>{{ __('Review') }}</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $this->disputes->links() }}</div>
            @endif
        </div>
    </div>
</div>
