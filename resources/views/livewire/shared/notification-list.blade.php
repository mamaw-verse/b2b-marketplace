<div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">

    {{-- Header --}}
    <div class="flex items-center justify-between px-6 py-5 border-b border-[var(--hs-neutral-100)]">
        <div>
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Notifications') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('All your recent activity in one place.') }}</p>
        </div>
        @if (auth()->user()->unreadNotifications()->exists())
            <flux:button wire:click="markAllRead" variant="ghost" size="sm">
                {{ __('Mark all read') }}
            </flux:button>
        @endif
    </div>

    {{-- List --}}
    <div class="divide-y divide-[var(--hs-neutral-100)]">
        @forelse ($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = is_null($notification->read_at);
                $message = $data['message'] ?? $data['body_preview'] ?? __('New notification');
            @endphp

            <button
                wire:click="markRead('{{ $notification->id }}')"
                wire:key="notification-{{ $notification->id }}"
                class="w-full flex items-start gap-3 px-6 py-4 text-left hover:bg-[var(--hs-neutral-50)] transition"
            >
                <div class="flex-1 min-w-0">
                    <p @class([
                        'text-sm text-[var(--hs-neutral-800)]',
                        'font-semibold' => $isUnread,
                        'font-normal' => ! $isUnread,
                    ])>
                        {{ $message }}
                    </p>
                    <p class="text-xs text-[var(--hs-neutral-500)] mt-0.5">
                        {{ $notification->created_at->diffForHumans() }}
                    </p>
                </div>

                @if ($isUnread)
                    <span class="mt-1.5 size-2 shrink-0 rounded-full bg-[var(--hs-green-500)]"></span>
                @endif
            </button>
        @empty
            <div class="px-6 py-16 text-center text-sm text-[var(--hs-neutral-500)]">
                {{ __('No notifications yet.') }}
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($notifications->hasPages())
        <div class="px-6 py-4 border-t border-[var(--hs-neutral-100)]">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
