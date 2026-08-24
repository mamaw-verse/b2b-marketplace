<div>
    {{-- Success flash for report --}}
    @if (session('review-reported'))
        <div class="rounded-lg border border-[var(--hs-success-200)] bg-[var(--hs-success-50)] p-3 mb-4">
            <p class="text-sm text-[var(--hs-success-700)]">{{ session('review-reported') }}</p>
        </div>
    @endif

    @if ($reviews->count() > 0)
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-[var(--hs-neutral-100)] flex items-center justify-between">
                <p class="text-sm font-semibold text-[var(--hs-neutral-700)]">
                    {{ $reviews->total() }} {{ Str::plural('Review', $reviews->total()) }}
                </p>
            </div>

            {{-- Review Items --}}
            <div class="divide-y divide-[var(--hs-neutral-100)]">
                @foreach ($reviews as $review)
                    <div class="px-6 py-5">
                        <div class="flex gap-3.5">
                            {{-- Avatar --}}
                            <div class="size-9 rounded-full bg-[var(--hs-green-50)] text-[var(--hs-green-700)] flex items-center justify-center text-xs font-bold shrink-0 border border-[var(--hs-green-200)]">
                                {{ strtoupper(mb_substr($review->reviewer->first_name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr($review->reviewer->last_name ?? '', 0, 1)) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                {{-- Name + Rating + Timestamp + Report --}}
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="text-sm font-semibold text-[var(--hs-neutral-900)]">
                                            {{ $review->reviewer->first_name }} {{ $review->reviewer->last_name }}
                                        </span>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            @for($i = 1; $i <= 5; $i++)
                                                <span class="text-sm {{ $review->rating >= $i ? 'text-yellow-400' : 'text-[var(--hs-neutral-200)]' }}">★</span>
                                            @endfor
                                            <span class="text-xs text-[var(--hs-neutral-400)] ml-1">{{ $review->rating }}/5</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-xs text-[var(--hs-neutral-400)]">{{ $review->created_at->diffForHumans() }}</span>
                                        @auth
                                            @if ($review->reviewer_user_id !== auth()->id())
                                                <button
                                                    type="button"
                                                    wire:click="openReportModal({{ $review->id }})"
                                                    class="text-xs text-[var(--hs-neutral-400)] hover:text-[var(--hs-danger-500)] transition"
                                                    title="Report this review"
                                                >
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" /></svg>
                                                </button>
                                            @endif
                                        @endauth
                                    </div>
                                </div>

                                {{-- Review Body --}}
                                @if ($review->body)
                                    <p class="mt-2.5 text-sm text-[var(--hs-neutral-700)] leading-relaxed">{{ $review->body }}</p>
                                @else
                                    <p class="mt-2.5 text-sm text-[var(--hs-neutral-400)] italic">No comment provided.</p>
                                @endif

                                {{-- Seller Response --}}
                                @if ($review->response)
                                    <div class="mt-3 rounded-lg bg-[var(--hs-green-50)] border border-[var(--hs-green-100)] px-4 py-3">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <svg class="size-3.5 text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                            <p class="text-xs font-semibold text-[var(--hs-green-700)]">Seller Response</p>
                                        </div>
                                        <p class="text-sm text-[var(--hs-green-800)] leading-relaxed">{{ $review->response->body }}</p>
                                    </div>
                                @endif
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
        </div>
    @else
        <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] px-6 py-10 text-center">
            <p class="text-sm text-[var(--hs-neutral-500)]">{{ __('No reviews yet. Be the first to leave a review!') }}</p>
        </div>
    @endif

    {{-- Report Review Modal --}}
    @if ($reportingReviewId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-on:keydown.escape.window="$wire.set('reportingReviewId', null)">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/40" wire:click="$set('reportingReviewId', null)"></div>

            {{-- Modal content --}}
            <div class="relative bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-xl w-full max-w-md p-6 space-y-4">
                <h3 class="text-lg font-semibold text-[var(--hs-neutral-900)]">Report Review</h3>
                <p class="text-sm text-[var(--hs-neutral-600)]">
                    Please tell us why you're reporting this review. Our moderation team will investigate.
                </p>

                <div>
                    <label class="block text-sm font-medium text-[var(--hs-neutral-700)] mb-1">Reason</label>
                    <textarea
                        wire:model="reportReason"
                        rows="3"
                        placeholder="Describe why this review is inappropriate..."
                        class="w-full rounded-[var(--hs-radius-md)] border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-3 py-2 text-sm text-[var(--hs-neutral-900)] placeholder:text-[var(--hs-neutral-400)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-600)] focus:border-transparent"
                    ></textarea>
                    @error('reportReason') <p class="text-sm text-[var(--hs-danger-700)] mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" wire:click="$set('reportingReviewId', null)">
                        Cancel
                    </flux:button>
                    <flux:button variant="danger" wire:click="submitReport">
                        Submit Report
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
