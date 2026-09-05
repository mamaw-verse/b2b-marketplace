<div>
    @php
        $existingReviews = $this->existingReviews;
        $reviewableProducts = $this->reviewableProducts;
        $shopReview = $existingReviews->where('reviewable_type', App\Models\Shop::class)->where('reviewable_id', $order->shop_id)->first();
        $hasAllReviews = $shopReview && $reviewableProducts->every(fn ($p) => $existingReviews->where('reviewable_type', App\Models\Product::class)->where('reviewable_id', $p->id)->first());
        $hasAnyReview = $existingReviews->isNotEmpty();
    @endphp

    {{-- Display existing reviews --}}
    @if ($hasAnyReview)
        <div class="space-y-3 mb-4">
            @if ($shopReview)
                <div class="flex items-start gap-3 p-3 rounded-lg bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-100)]">
                    <div class="flex-1">
                        @if ($editingReviewId === $shopReview->id)
                            <p class="text-xs font-semibold text-[var(--hs-neutral-500)]">{{ __('Edit Seller Review') }}</p>
                            <div class="flex items-center gap-1 mt-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('editRating', {{ $i }})"
                                        class="text-xl transition {{ $editRating >= $i ? 'text-yellow-400' : 'text-[var(--hs-neutral-300)]' }}"
                                        aria-label="{{ __('Rate :i stars', ['i' => $i]) }}">
                                        ★
                                    </button>
                                @endfor
                                @if($editRating > 0)
                                    <span class="ml-2 text-xs text-[var(--hs-neutral-500)]">{{ $editRating }}/5</span>
                                @endif
                            </div>
                            <div class="mt-2">
                                <flux:textarea wire:model="editBody" placeholder="{{ __('Update your review (optional)') }}" rows="2" />
                            </div>
                            <div class="mt-2 flex gap-2">
                                <flux:button wire:click="updateReview" variant="primary" size="xs">{{ __('Save Changes') }}</flux:button>
                                <flux:button wire:click="cancelEdit" variant="ghost" size="xs">{{ __('Cancel') }}</flux:button>
                            </div>
                        @else
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold text-[var(--hs-neutral-500)]">{{ __('Seller Review') }}</p>
                                @unless($shopReview->response)
                                    <button wire:click="editReview({{ $shopReview->id }})" class="text-xs text-[var(--hs-green-600)] hover:text-[var(--hs-green-700)] font-medium">{{ __('Edit') }}</button>
                                @endunless
                            </div>
                            <div class="flex items-center gap-1 mt-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-sm {{ $shopReview->rating >= $i ? 'text-yellow-400' : 'text-[var(--hs-neutral-300)]' }}">★</span>
                                @endfor
                                <span class="text-xs text-[var(--hs-neutral-500)] ml-1">{{ $shopReview->rating }}/5</span>
                            </div>
                            @if ($shopReview->body)
                                <p class="text-sm text-[var(--hs-neutral-700)] mt-1">{{ $shopReview->body }}</p>
                            @endif
                            @if ($shopReview->response)
                                <div class="mt-2 rounded-[var(--hs-radius-sm)] border-l-4 border-[var(--hs-green-300)] bg-[var(--hs-green-50)] p-2.5">
                                    <p class="text-xs font-medium text-[var(--hs-green-700)]">{{ __('Seller Reply') }} — {{ $shopReview->response->responder->first_name }}</p>
                                    <p class="mt-0.5 text-sm text-[var(--hs-green-800)]">{{ $shopReview->response->body }}</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            @foreach ($reviewableProducts as $product)
                @php $productReview = $existingReviews->where('reviewable_type', App\Models\Product::class)->where('reviewable_id', $product->id)->first(); @endphp
                @if ($productReview)
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-[var(--hs-neutral-50)] border border-[var(--hs-neutral-100)]">
                        <div class="flex-1">
                            @if ($editingReviewId === $productReview->id)
                                <p class="text-xs font-semibold text-[var(--hs-neutral-500)]">{{ __('Edit Product: :name', ['name' => $product->name]) }}</p>
                                <div class="flex items-center gap-1 mt-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" wire:click="$set('editRating', {{ $i }})"
                                            class="text-xl transition {{ $editRating >= $i ? 'text-yellow-400' : 'text-[var(--hs-neutral-300)]' }}"
                                            aria-label="{{ __('Rate :i stars', ['i' => $i]) }}">
                                            ★
                                        </button>
                                    @endfor
                                    @if($editRating > 0)
                                        <span class="ml-2 text-xs text-[var(--hs-neutral-500)]">{{ $editRating }}/5</span>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <flux:textarea wire:model="editBody" placeholder="{{ __('Update your review (optional)') }}" rows="2" />
                                </div>
                                <div class="mt-2 flex gap-2">
                                    <flux:button wire:click="updateReview" variant="primary" size="xs">{{ __('Save Changes') }}</flux:button>
                                    <flux:button wire:click="cancelEdit" variant="ghost" size="xs">{{ __('Cancel') }}</flux:button>
                                </div>
                            @else
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-[var(--hs-neutral-500)]">{{ __('Product: :name', ['name' => $product->name]) }}</p>
                                    @unless($productReview->response)
                                        <button wire:click="editReview({{ $productReview->id }})" class="text-xs text-[var(--hs-green-600)] hover:text-[var(--hs-green-700)] font-medium">{{ __('Edit') }}</button>
                                    @endunless
                                </div>
                                <div class="flex items-center gap-1 mt-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="text-sm {{ $productReview->rating >= $i ? 'text-yellow-400' : 'text-[var(--hs-neutral-300)]' }}">★</span>
                                    @endfor
                                    <span class="text-xs text-[var(--hs-neutral-500)] ml-1">{{ $productReview->rating }}/5</span>
                                </div>
                                @if ($productReview->body)
                                    <p class="text-sm text-[var(--hs-neutral-700)] mt-1">{{ $productReview->body }}</p>
                                @endif
                                @if ($productReview->response)
                                    <div class="mt-2 rounded-[var(--hs-radius-sm)] border-l-4 border-[var(--hs-green-300)] bg-[var(--hs-green-50)] p-2.5">
                                        <p class="text-xs font-medium text-[var(--hs-green-700)]">{{ __('Seller Reply') }} — {{ $productReview->response->responder->first_name }}</p>
                                        <p class="mt-0.5 text-sm text-[var(--hs-green-800)]">{{ $productReview->response->body }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Success flash --}}
    @if (session('review-success'))
        <div class="rounded-lg border border-[var(--hs-success-200)] bg-[var(--hs-success-50)] p-3 mb-4">
            <p class="text-sm text-[var(--hs-success-700)]">{{ session('review-success') }}</p>
        </div>
    @endif

    {{-- Show "Write a Review" button only if not all reviews are submitted --}}
    @if ($order->status === 'completed' && !$hasAllReviews && !$showForm)
        <flux:button wire:click="openForm" variant="primary" size="sm">
            {{ $hasAnyReview ? __('Add More Reviews') : __('Write a Review') }}
        </flux:button>
    @endif

    {{-- Review Form --}}
    @if ($showForm)
        <div class="rounded-lg border border-[var(--hs-neutral-200)] p-6">
            @error('form')
                <p class="text-sm text-[var(--hs-danger-700)] mb-4">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Left Column: Seller Review --}}
                <div class="space-y-3">
                    <h4 class="text-sm font-semibold text-[var(--hs-neutral-700)]">{{ __('Rate the Seller') }}</h4>
                    <p class="text-xs text-[var(--hs-neutral-500)]">{{ $order->shop->storefront_name }}</p>

                    @if ($shopReview)
                        <p class="text-xs text-[var(--hs-neutral-500)] italic">{{ __('Already reviewed') }} ✓</p>
                    @else
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('shopRating', {{ $i }})"
                                    class="text-2xl transition {{ $shopRating >= $i ? 'text-yellow-400' : 'text-[var(--hs-neutral-300)]' }}"
                                    aria-label="{{ __('Rate seller :i stars', ['i' => $i]) }}">
                                    ★
                                </button>
                            @endfor
                            @if($shopRating > 0)
                                <span class="ml-2 text-xs text-[var(--hs-neutral-500)]">{{ $shopRating }}/5</span>
                            @endif
                        </div>
                        @if($shopRating > 0)
                            <flux:textarea wire:model="shopBody" placeholder="{{ __('Share your experience with this seller (optional)') }}" rows="3" />
                        @endif
                    @endif
                </div>

                {{-- Right Column: Product Reviews (per product) --}}
                <div class="space-y-6">
                    @foreach ($reviewableProducts as $product)
                            @php $existingProductReview = $existingReviews->where('reviewable_type', App\Models\Product::class)->where('reviewable_id', $product->id)->first(); @endphp
                            <div class="space-y-3">
                                <h4 class="text-sm font-semibold text-[var(--hs-neutral-700)]">{{ __('Rate Product') }}</h4>
                                <p class="text-xs text-[var(--hs-neutral-500)]">{{ $product->name }}</p>

                                @if ($existingProductReview)
                                    <p class="text-xs text-[var(--hs-neutral-500)] italic">{{ __('Already reviewed') }} ✓</p>
                                @else
                                    <div class="flex items-center gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <button type="button" wire:click="$set('productReviews.{{ $product->id }}.rating', {{ $i }})"
                                                class="text-2xl transition {{ ($productReviews[$product->id]['rating'] ?? 0) >= $i ? 'text-yellow-400' : 'text-[var(--hs-neutral-300)]' }}"
                                                aria-label="{{ __('Rate product :i stars', ['i' => $i]) }}">
                                                ★
                                            </button>
                                        @endfor
                                        @if(($productReviews[$product->id]['rating'] ?? 0) > 0)
                                            <span class="ml-2 text-xs text-[var(--hs-neutral-500)]">{{ $productReviews[$product->id]['rating'] }}/5</span>
                                        @endif
                                    </div>
                                    @if(($productReviews[$product->id]['rating'] ?? 0) > 0)
                                        <flux:textarea wire:model="productReviews.{{ $product->id }}.body" placeholder="{{ __('Share your experience with this product (optional)') }}" rows="3" />
                                    @endif
                                @endif
                            </div>
                        @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 mt-6 pt-4 border-t border-[var(--hs-neutral-100)]">
                @php
                    $canSubmit = $shopRating > 0 || collect($productReviews)->contains(fn ($r) => ($r['rating'] ?? 0) > 0);
                @endphp
<flux:button wire:click="submit" variant="primary" :disabled="!$canSubmit">
                    {{ __('Submit Review') }}
                </flux:button>
                <flux:button wire:click="$set('showForm', false)" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    @endif
</div>
