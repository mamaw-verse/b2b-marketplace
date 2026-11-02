<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)] overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Review Reports') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Flagged reviews pending moderation.') }}</p>
        </div>

        {{-- Reports List --}}
        @if ($reports->count() > 0)
            <div class="divide-y divide-[var(--hs-neutral-100)]">
                @foreach ($reports as $report)
                    <div class="px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0 space-y-2">
                                {{-- Reporter info --}}
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-[var(--hs-neutral-400)]">{{ __('Reported by') }}</span>
                                    <span class="text-sm font-medium text-[var(--hs-neutral-700)]">
                                        {{ $report->reporter->first_name }} {{ $report->reporter->last_name }}
                                    </span>
                                    <span class="text-xs text-[var(--hs-neutral-400)]">{{ $report->created_at->diffForHumans() }}</span>
                                </div>

                                {{-- The reported review --}}
                                <div class="rounded-lg border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] p-4">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="text-sm font-semibold text-[var(--hs-neutral-800)]">
                                            {{ $report->review->reviewer->first_name }} {{ $report->review->reviewer->last_name }}
                                        </span>
                                        <div class="flex items-center gap-0.5">
                                            @for($i = 1; $i <= 5; $i++)
                                                <span class="text-xs {{ $report->review->rating >= $i ? 'text-[var(--hs-warning-500)]' : 'text-[var(--hs-neutral-300)]' }}">★</span>
                                            @endfor
                                        </div>
                                    </div>
                                    @if ($report->review->body)
                                        <p class="text-sm text-[var(--hs-neutral-700)] leading-relaxed">{{ Str::limit($report->review->body, 300) }}</p>
                                    @else
                                        <p class="text-sm text-[var(--hs-neutral-400)] italic">{{ __('Rating only — no comment.') }}</p>
                                    @endif
                                </div>

                                {{-- Report reason --}}
                                <div class="flex items-start gap-2">
                                    <svg class="size-4 text-[var(--hs-danger-500)] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" /></svg>
                                    <p class="text-sm text-[var(--hs-danger-700)]">{{ $report->reason }}</p>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex flex-col gap-2 shrink-0">
                                <flux:button wire:click="dismiss({{ $report->id }})" variant="ghost" size="sm">
                                    {{ __('Dismiss') }}
                                </flux:button>
                                <flux:button wire:click="action({{ $report->id }})" variant="danger" size="sm">
                                    {{ __('Hide Review') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if ($reports->hasPages())
                <div class="px-6 py-3 border-t border-[var(--hs-neutral-100)]">
                    {{ $reports->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-16 text-center">
                <x-hs-empty-state
                    icon="check-circle"
                    title="{{ __('All clear') }}"
                    subtitle="{{ __('No pending review reports. All flagged reviews have been handled.') }}"
                />
            </div>
        @endif
    </div>
</div>
