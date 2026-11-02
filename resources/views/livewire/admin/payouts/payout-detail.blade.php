<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Payout #:id', ['id' => $payout->id]) }}</h1>
                <p class="text-sm text-[var(--hs-neutral-500)]">{{ $payout->shop->storefront_name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-hs-status-pill :status="$payout->status" />
                @if ($payout->status === 'pending')
                    <flux:modal.trigger name="confirm-release-payout">
                        <flux:button size="sm" variant="primary">{{ __('Release') }}</flux:button>
                    </flux:modal.trigger>
                @elseif ($payout->status === 'failed')
                    <flux:modal.trigger name="confirm-retry-payout">
                        <flux:button size="sm" variant="primary">{{ __('Retry') }}</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </div>

        @session('success')
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-success-50)] border border-[var(--hs-success-200)] rounded-lg text-sm text-[var(--hs-success-700)]">{{ $value }}</div>
        @endsession
        @session('error')
            <div class="mx-6 mt-4 p-3 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-sm text-[var(--hs-danger-700)]">{{ $value }}</div>
        @endsession

        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 border-b border-[var(--hs-neutral-100)]">
            <div>
                <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Gross Amount') }}</p>
                <p class="text-lg font-semibold text-[var(--hs-neutral-900)]">₱{{ number_format((float) $payout->gross_amount, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Transaction Fees') }}</p>
                <p class="text-lg font-semibold text-[var(--hs-danger-700)]">-₱{{ number_format((float) $payout->total_transaction_fees, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Commission Fees') }}</p>
                <p class="text-lg font-semibold text-[var(--hs-danger-700)]">-₱{{ number_format((float) $payout->total_commission_fees, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-[var(--hs-neutral-500)]">{{ __('Net Amount') }}</p>
                <p class="text-lg font-semibold text-[var(--hs-green-700)]">₱{{ number_format((float) $payout->net_amount, 2) }}</p>
            </div>
        </div>

        {{-- Payout Items --}}
        <div class="p-6 border-b border-[var(--hs-neutral-100)]">
            <h2 class="text-sm font-semibold text-[var(--hs-neutral-700)] mb-3">{{ __('Orders (:count)', ['count' => $payout->orders_count]) }}</h2>

            {{-- Mobile: stacked card list --}}
            <div class="lg:hidden divide-y divide-[var(--hs-neutral-100)]">
                @foreach ($payout->items as $item)
                    <div class="py-3">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-medium text-[var(--hs-neutral-900)]">#{{ $item->order_id }}</p>
                            <p class="font-mono text-[var(--hs-green-700)] text-right">₱{{ number_format((float) $item->net_amount, 2) }}</p>
                        </div>
                        <dl class="mt-2 grid grid-cols-3 gap-x-2 text-xs">
                            <div>
                                <dt class="text-[var(--hs-neutral-500)]">{{ __('Escrowed') }}</dt>
                                <dd class="font-mono text-[var(--hs-neutral-700)]">₱{{ number_format((float) $item->escrowed_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-[var(--hs-neutral-500)]">{{ __('Tx Fee') }}</dt>
                                <dd class="font-mono text-[var(--hs-danger-700)]">-₱{{ number_format((float) $item->transaction_fee, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-[var(--hs-neutral-500)]">{{ __('Commission') }}</dt>
                                <dd class="font-mono text-[var(--hs-danger-700)]">-₱{{ number_format((float) $item->commission_fee, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>

            {{-- Desktop: table --}}
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-[var(--hs-neutral-100)]">
                        <th class="text-left py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Order') }}</th>
                        <th class="text-right py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Escrowed') }}</th>
                        <th class="text-right py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Tx Fee') }}</th>
                        <th class="text-right py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Commission') }}</th>
                        <th class="text-right py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Net') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($payout->items as $item)
                            <tr class="border-b border-[var(--hs-neutral-50)]">
                                <td class="py-2 px-2 font-medium text-[var(--hs-neutral-900)]">#{{ $item->order_id }}</td>
                                <td class="py-2 px-2 text-right font-mono">₱{{ number_format((float) $item->escrowed_amount, 2) }}</td>
                                <td class="py-2 px-2 text-right font-mono text-[var(--hs-danger-700)]">-₱{{ number_format((float) $item->transaction_fee, 2) }}</td>
                                <td class="py-2 px-2 text-right font-mono text-[var(--hs-danger-700)]">-₱{{ number_format((float) $item->commission_fee, 2) }}</td>
                                <td class="py-2 px-2 text-right font-mono text-[var(--hs-green-700)]">₱{{ number_format((float) $item->net_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Disbursement History --}}
        <div class="p-6">
            <h2 class="text-sm font-semibold text-[var(--hs-neutral-700)] mb-3">{{ __('Disbursement History') }}</h2>
            @if ($payout->disbursements->isEmpty())
                <p class="text-sm text-[var(--hs-neutral-500)]">{{ __('No disbursement attempts yet.') }}</p>
            @else
                {{-- Mobile: stacked card list --}}
                <div class="lg:hidden divide-y divide-[var(--hs-neutral-100)]">
                    @foreach ($payout->disbursements as $disbursement)
                        <div class="py-3">
                            <div class="flex items-start justify-between gap-3">
                                <x-hs-status-pill :status="$disbursement->status" />
                                <p class="font-mono text-[var(--hs-neutral-900)] text-right">₱{{ number_format((float) $disbursement->amount, 2) }}</p>
                            </div>
                            <dl class="mt-2 grid grid-cols-2 gap-x-4 text-xs">
                                <div>
                                    <dt class="text-[var(--hs-neutral-500)]">{{ __('Reference') }}</dt>
                                    <dd class="font-mono text-[var(--hs-neutral-700)]">{{ $disbursement->dlt_reference_id ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[var(--hs-neutral-500)]">{{ __('Attempted') }}</dt>
                                    <dd class="text-[var(--hs-neutral-500)]">{{ $disbursement->attempted_at?->diffForHumans() ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endforeach
                </div>

                {{-- Desktop: table --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-[var(--hs-neutral-100)]">
                            <th class="text-left py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Status') }}</th>
                            <th class="text-left py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Reference') }}</th>
                            <th class="text-right py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Amount') }}</th>
                            <th class="text-left py-2 px-2 text-[var(--hs-neutral-600)]">{{ __('Attempted') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($payout->disbursements as $disbursement)
                                <tr class="border-b border-[var(--hs-neutral-50)]">
                                    <td class="py-2 px-2"><x-hs-status-pill :status="$disbursement->status" /></td>
                                    <td class="py-2 px-2 text-[var(--hs-neutral-700)] font-mono text-xs">{{ $disbursement->dlt_reference_id ?? '—' }}</td>
                                    <td class="py-2 px-2 text-right font-mono">₱{{ number_format((float) $disbursement->amount, 2) }}</td>
                                    <td class="py-2 px-2 text-[var(--hs-neutral-500)]">{{ $disbursement->attempted_at?->diffForHumans() ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Release Confirmation --}}
    <x-hs-confirm
        name="confirm-release-payout"
        title="{{ __('Release Payout') }}"
        message="{{ __('Disburse ₱:amount to :shop? This will initiate a bank transfer and cannot be reversed.', ['amount' => number_format((float) $payout->net_amount, 2), 'shop' => $payout->shop->storefront_name]) }}"
        variant="primary"
        confirmText="{{ __('Release Payout') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="release" wire:loading.attr="disabled">
                {{ __('Release Payout') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>

    {{-- Retry Confirmation --}}
    <x-hs-confirm
        name="confirm-retry-payout"
        title="{{ __('Retry Payout') }}"
        message="{{ __('Retry the failed disbursement of ₱:amount to :shop?', ['amount' => number_format((float) $payout->net_amount, 2), 'shop' => $payout->shop->storefront_name]) }}"
        variant="warning"
        confirmText="{{ __('Retry') }}"
    >
        <x-slot name="action">
            <flux:button variant="primary" wire:click="retry" wire:loading.attr="disabled">
                {{ __('Retry') }}
            </flux:button>
        </x-slot>
    </x-hs-confirm>
</div>
