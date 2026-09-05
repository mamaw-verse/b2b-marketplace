<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Escalated Disputes') }}</h1>
        </div>
        <div class="p-6">
            @if ($this->disputes->isEmpty())
                <p class="text-center text-[var(--hs-neutral-500)] py-8">{{ __('No escalated disputes.') }}</p>
            @else
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
                <div class="mt-4">{{ $this->disputes->links() }}</div>
            @endif
        </div>
    </div>
</div>
