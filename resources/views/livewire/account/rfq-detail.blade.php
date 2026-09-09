<div>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
            <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ $rfq->title }}</h1>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">
                            {{ __('Sent') }} {{ $rfq->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <x-hs-status-pill :status="$this->adminStatus" class="shrink-0" />
                </div>
            </div>

            {{-- Product being quoted --}}
            @if ($rfq->product)
                <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
                    <p class="text-xs font-medium uppercase tracking-[0.05em] text-[var(--hs-neutral-500)] mb-3">{{ __('Product for quotation') }}</p>
                    <div class="flex items-center gap-3">
                        <div class="size-12 shrink-0 overflow-hidden rounded-[var(--hs-radius-md)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)]">
                            @if ($rfq->product->featured_image_path)
                                <img
                                    src="{{ $rfq->product->featured_image_url }}"
                                    alt="{{ $rfq->product->name }}"
                                    class="size-full object-cover"
                                    loading="lazy"
                                />
                            @else
                                <div class="flex size-full items-center justify-center">
                                    <flux:icon name="photo" class="size-5 text-[var(--hs-neutral-300)]" />
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('storefront.product', $rfq->product) }}" wire:navigate
                               class="block truncate text-sm font-semibold text-[var(--hs-neutral-900)] transition hover:text-[var(--hs-green-600)]">
                                {{ $rfq->product->name }}
                            </a>
                            @php $cheapestVariant = $rfq->product->variants->sortBy('price')->first(); @endphp
                            @if ($cheapestVariant)
                                <p class="mt-1 text-sm font-bold text-[var(--hs-green-700)]">
                                    {{ $cheapestVariant->currency ?? 'PHP' }} {{ number_format((float) $cheapestVariant->price, 2) }}
                                </p>
                                @if ($cheapestVariant->minimum_order_quantity)
                                    <p class="mt-0.5 text-xs text-[var(--hs-neutral-500)]">
                                        {{ __('MOQ: :quantity :unit', ['quantity' => $cheapestVariant->minimum_order_quantity, 'unit' => $cheapestVariant->unit ?? 'pcs']) }}
                                    </p>
                                @endif
                            @endif
                        </div>
                        <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square" href="{{ route('storefront.product', $rfq->product) }}" wire:navigate class="hidden shrink-0 sm:inline-flex">
                            {{ __('View') }}
                        </flux:button>
                    </div>
                </div>
            @endif

            {{-- Description --}}
            <div class="p-6">
                @if ($rfq->description)
                    <div class="prose prose-sm max-w-none text-[var(--hs-neutral-700)]">
                        {!! nl2br(e($rfq->description)) !!}
                    </div>
                @else
                    <p class="text-sm text-[var(--hs-neutral-500)] italic">{{ __('No description provided.') }}</p>
                @endif
            </div>
        </div>

        {{-- Attachments --}}
        @if ($rfq->attachments->count() > 0)
            <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
                <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)]">
                    <h2 class="text-base font-semibold text-[var(--hs-neutral-800)]">{{ __('Attachments') }}</h2>
                </div>
                <div class="p-3">
                    <ul class="divide-y divide-[var(--hs-neutral-100)]">
                        @foreach ($rfq->attachments as $attachment)
                            <li>
                                <a
                                    href="{{ route('media.view', $attachment) }}"
                                    target="_blank"
                                    class="flex items-center gap-3 rounded-[var(--hs-radius-card)] px-3 py-2.5 text-sm text-[var(--hs-neutral-700)] hover:bg-[var(--hs-neutral-50)] transition"
                                >
                                    <flux:icon name="paper-clip" variant="mini" class="shrink-0 text-[var(--hs-neutral-400)]" />
                                    <span class="truncate">{{ $attachment->original_filename }}</span>
                                    <flux:icon name="arrow-top-right-on-square" variant="mini" class="ml-auto shrink-0 text-[var(--hs-neutral-300)]" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
</div>
