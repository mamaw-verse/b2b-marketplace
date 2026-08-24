<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">Reviews</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">Reviews from your customers on your shop and products.</p>
        </div>

        {{-- Filters --}}
        <div class="px-6 py-3 border-b border-[var(--hs-neutral-100)] flex items-center gap-4">
            <flux:select wire:model.live="ratingFilter" size="sm" class="w-36">
                <flux:select.option value="">All Ratings</flux:select.option>
                @for($i = 5; $i >= 1; $i--)
                    <flux:select.option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</flux:select.option>
                @endfor
            </flux:select>
        </div>

        {{-- Reviews List --}}
        @if ($reviews->count() > 0)
            <div class="divide-y divide-[var(--hs-neutral-100)]">
                @foreach ($reviews as $review)
                    <div class="px-6 py-5">
                        <div class="flex gap-4">
                            {{-- Avatar --}}
                            <div class="size-9 rounded-full bg-[var(--hs-gold-100)] text-[var(--hs-gold-600)] flex items-center justify-center text-xs font-bold shrink-0">
                                {{ strtoupper(mb_substr($review->reviewer->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($review->reviewer->last_name ?? '', 0, 1)) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                {{-- Top row --}}
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-[var(--hs-neutral-900)]">
                                                {{ $review->reviewer->first_name }} {{ $review->reviewer->last_name }}
                                            </span>
                                            <x-hs-badge
                                                variant="{{ $review->reviewable_type === \App\Models\Shop::class ? 'verified' : 'tag' }}"
                                                :label="$review->reviewable_type === \App\Models\Shop::class ? 'Shop Review' : ($review->reviewable?->name ?? 'Product')"
                                            />
                                        </div>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            @for($i = 1; $i <= 5; $i++)
                                                <span class="text-sm {{ $review->rating >= $i ? 'text-yellow-400' : 'text-[var(--hs-neutral-200)]' }}">★</span>
                                            @endfor
                                            <span class="text-xs text-[var(--hs-neutral-400)] ml-1">{{ $review->rating }}/5</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        @if ($review->is_hidden)
                                            <x-hs-badge variant="danger" label="Hidden" />
                                        @endif
                                        <span class="text-xs text-[var(--hs-neutral-400)]">{{ $review->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>

                                {{-- Review body --}}
                                @if ($review->body)
                                    <p class="mt-2 text-sm text-[var(--hs-neutral-700)] leading-relaxed">{{ $review->body }}</p>
                                @else
                                    <p class="mt-2 text-sm text-[var(--hs-neutral-400)] italic">Rating only — no comment.</p>
                                @endif

                                {{-- Seller Response --}}
                                <div class="mt-3">
                                    <livewire:company-hub.reviews.review-response-form :review="$review" :key="'response-'.$review->id" />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if ($reviews->hasPages())
                <div class="px-6 py-3 border-t border-[var(--hs-neutral-100)]">
                    {{ $reviews->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-16 text-center">
                <x-hs-empty-state
                    icon="star"
                    title="No reviews yet"
                    subtitle="Reviews from your customers will appear here once they leave feedback on completed orders."
                />
            </div>
        @endif
    </div>
</div>
