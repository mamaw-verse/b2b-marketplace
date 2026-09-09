<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">{{ $rfq->title }}</flux:heading>
            <flux:subheading>{{ __('Manage RFQ from :buyer', ['buyer' => $recipient->shop->registered_business_name ?? __('Unknown Buyer')]) }}</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Left Column: Buyer & RFQ Info --}}
        <div class="space-y-6 md:col-span-2">
            
            {{-- RFQ Content --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('RFQ Details') }}</flux:heading>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-zinc-500">{{ __('Target Shop') }}</div>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white">
                            <a href="{{ route('admin.merchants.show', $recipient->shop_id) }}" class="text-[var(--hs-green-700)] hover:underline" wire:navigate>
                                {{ $recipient->shop->registered_business_name }}
                            </a>
                        </div>
                    </div>

                    @if($rfq->product)
                        <div>
                            <div class="text-sm font-medium text-zinc-500">{{ __('Product for Quotation') }}</div>
                            <div class="mt-2 flex items-center gap-3 rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-3">
                                <div class="size-14 shrink-0 overflow-hidden rounded-md border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
                                    @if($rfq->product->featured_image_path)
                                        <img src="{{ $rfq->product->featured_image_url }}" alt="{{ $rfq->product->name }}" class="size-full object-cover" loading="lazy" />
                                    @else
                                        <div class="flex size-full items-center justify-center">
                                            <svg class="size-5 text-[var(--hs-neutral-300)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75 5.159 5.159A2.25 2.25 0 0 1 7.322 3.75h9.356a2.25 2.25 0 0 1 2.163 1.409l2.909 10.591M2.25 15.75h19.5M2.25 15.75v4.5a2.25 2.25 0 0 0 2.25 2.25h15a2.25 2.25 0 0 0 2.25-2.25v-4.5M6.75 3V2.25a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 .75.75V3" /></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('storefront.product', $rfq->product) }}" target="_blank" class="block truncate text-sm font-semibold text-[var(--hs-green-700)] hover:underline">
                                        {{ $rfq->product->name }}
                                    </a>
                                    @php $cheapestVariant = $rfq->product->variants->sortBy('price')->first(); @endphp
                                    @if($cheapestVariant)
                                        <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ $cheapestVariant->currency ?? 'PHP' }} {{ number_format((float)$cheapestVariant->price, 2) }}
                                            @if($cheapestVariant->minimum_order_quantity)
                                                · {{ __('MOQ: :quantity :unit', ['quantity' => $cheapestVariant->minimum_order_quantity, 'unit' => $cheapestVariant->unit ?? 'pcs']) }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <div class="text-sm font-medium text-zinc-500">{{ __('Description') }}</div>
                        <div class="mt-1 text-sm text-zinc-900 dark:text-white whitespace-pre-wrap">{{ $rfq->description ?: 'No description provided.' }}</div>
                    </div>
                </div>

                @if($rfq->attachments->isNotEmpty())
                    <div class="mt-6">
                        <div class="text-sm font-medium text-zinc-500 mb-2">{{ __('Attachments') }}</div>
                        <div class="flex flex-col gap-2">
                            @foreach($rfq->attachments as $attachment)
                                <a href="{{ route('media.view', $attachment) }}" target="_blank" class="flex items-center gap-2 p-3 rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] hover:bg-[var(--hs-neutral-100)] transition group">
                                    <svg class="size-5 text-[var(--hs-neutral-400)] group-hover:text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                    </svg>
                                    <span class="text-sm font-medium text-[var(--hs-neutral-700)]">{{ $attachment->original_filename }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </flux:card>

            {{-- Buyer Snapshot --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Buyer Information') }}</flux:heading>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Contact Email') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $rfq->buyer_snapshot['contact_email'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Contact Phone') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">{{ $rfq->buyer_snapshot['contact_phone'] ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-zinc-500">{{ __('Location') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-white">
                            {{ $rfq->buyer_snapshot['shipping_city'] ?? '—' }}
                            @if(!empty($rfq->buyer_snapshot['shipping_city']) && !empty($rfq->buyer_snapshot['shipping_country_code'])) , @endif
                            {{ $rfq->buyer_snapshot['shipping_country_code'] ?? '—' }}
                        </dd>
                    </div>
                </dl>
                @if ($rfq->buyer?->shop)
                    <div class="mt-5 pt-4 border-t border-[var(--hs-neutral-100)]">
                        <flux:button
                            href="{{ route('admin.merchants.show', $rfq->buyer->shop) }}"
                            variant="ghost"
                            size="sm"
                            icon="arrow-top-right-on-square"
                            wire:navigate
                        >
                            {{ __('View more info') }}
                        </flux:button>
                    </div>
                @endif
            </flux:card>
        </div>

        {{-- Right Column: Admin Management --}}
        <div class="space-y-6">
            <flux:card>
                <form wire:submit="saveAdminDetails" class="space-y-5">
                    <flux:heading size="lg" class="mb-2">{{ __('Admin Management') }}</flux:heading>
                    
                    <flux:select wire:model="adminStatus" label="{{ __('Admin Status') }}">
                        <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                        <flux:select.option value="in_progress">{{ __('In Progress') }}</flux:select.option>
                        <flux:select.option value="closed">{{ __('Closed') }}</flux:select.option>
                    </flux:select>

                    <flux:textarea wire:model="adminNotes" label="{{ __('Internal Notes') }}" rows="5" placeholder="{{ __('Add private notes for admins...') }}" />

                    <div class="pt-2">
                        <flux:button type="submit" variant="primary" class="w-full">{{ __('Save Changes') }}</flux:button>
                    </div>

                    {{--
                    @if($recipient->managedBy)
                        <div class="mt-4 text-xs text-zinc-500 border-t border-[var(--hs-neutral-100)] pt-4">
                            {{ __('Managed by:') }} <span class="font-medium text-zinc-700">{{ $recipient->managedBy->name }}</span>
                        </div>
                    @endif
                    --}}
                </form>
            </flux:card>
        </div>
    </div>
</div>
