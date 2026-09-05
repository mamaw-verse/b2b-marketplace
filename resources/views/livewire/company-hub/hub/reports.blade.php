<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Sales Reports') }}</h1>
            <flux:select wire:model.live="window" class="w-40">
                <option value="7">{{ __('Last 7 days') }}</option>
                <option value="30">{{ __('Last 30 days') }}</option>
                <option value="90">{{ __('Last 90 days') }}</option>
            </flux:select>
        </div>
        <div class="p-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
            @foreach ([
                ['label' => __('Orders Placed'), 'value' => $metrics['orders_placed'] ?? 0, 'format' => 'number'],
                ['label' => __('Gross Sales'), 'value' => $metrics['gross_sales'] ?? 0, 'format' => 'currency'],
                ['label' => __('Refunds'), 'value' => $metrics['total_refunds'] ?? 0, 'format' => 'currency'],
                ['label' => __('Commission'), 'value' => $metrics['platform_commission'] ?? 0, 'format' => 'currency'],
                ['label' => __('Net Sales'), 'value' => $metrics['net_sales'] ?? 0, 'format' => 'currency'],
            ] as $metric)
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">
                        @if ($metric['format'] === 'currency') PHP {{ number_format((float) $metric['value'], 2) }} @else {{ $metric['value'] }} @endif
                    </p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">{{ $metric['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Payout Metrics --}}
    <div class="mt-6 bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Payout Summary') }}</h2>
        </div>
        <div class="p-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
            @foreach ([
                ['label' => __('Total Earned'), 'value' => $metrics['total_earned'] ?? 0],
                ['label' => __('Pending Balance'), 'value' => $metrics['pending_balance'] ?? 0],
                ['label' => __('Total Released'), 'value' => $metrics['total_released'] ?? 0],
            ] as $metric)
                <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                    <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">
                        PHP {{ number_format((float) $metric['value'], 2) }}
                    </p>
                    <p class="text-sm text-[var(--hs-neutral-600)] mt-1">{{ $metric['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Review Metrics --}}
    <div class="mt-6 bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h2 class="text-lg font-semibold text-[var(--hs-neutral-900)]">{{ __('Reviews') }}</h2>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4">
            <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">
                    @if (($metrics['total_reviews'] ?? 0) > 0)
                        {{ number_format((float) ($metrics['average_rating'] ?? 0), 2) }} <span class="text-base font-normal text-[var(--hs-neutral-500)]">/ 5.00</span>
                    @else
                        —
                    @endif
                </p>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">{{ __('Average Rating') }}</p>
            </div>
            <div class="p-4 rounded-lg bg-[var(--hs-neutral-50)]">
                <p class="text-2xl font-bold text-[var(--hs-neutral-900)]">{{ $metrics['total_reviews'] ?? 0 }}</p>
                <p class="text-sm text-[var(--hs-neutral-600)] mt-1">{{ __('Total Reviews') }}</p>
            </div>
        </div>
    </div>
</div>
