<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
    class="relative"
>
    {{-- Bell Button --}}
    <button
        id="notification-bell-btn"
        @click="open = !open"
        class="relative flex items-center justify-center size-9 rounded-lg text-[var(--hs-neutral-600)] hover:bg-[var(--hs-neutral-100)] hover:text-[var(--hs-neutral-900)] transition"
        aria-label="{{ __('Notifications') }}"
        :aria-expanded="open"
    >
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>

        @if ($this->unreadCount > 0)
            <span class="absolute top-1 right-1 flex size-4 items-center justify-center rounded-full text-[10px] font-bold text-white leading-none" style="background-color: var(--hs-danger-500);">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
        class="absolute right-0 mt-1 w-80 origin-top-right rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-lg)] z-50"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--hs-neutral-100)]">
            <p class="text-sm font-semibold text-[var(--hs-neutral-900)]">{{ __('Notifications') }}</p>
            @if ($this->unreadCount > 0)
                <button
                    wire:click="markAllRead"
                    class="text-xs text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)] font-medium transition"
                >
                    {{ __('Mark all read') }}
                </button>
            @endif
        </div>

        {{-- Notification List --}}
        <div class="divide-y divide-[var(--hs-neutral-100)] max-h-80 overflow-y-auto">
            @forelse ($this->notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = is_null($notification->read_at);
                    $type = $data['type'] ?? 'info';
                    $message = $data['message'] ?? $data['body_preview'] ?? __('New notification');
                @endphp

                <button
                    wire:click="markRead('{{ $notification->id }}')"
                    @click="open = false"
                    class="w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-[var(--hs-neutral-50)] transition"
                >
                    {{-- Type icon --}}
                    <div @class([
                        'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full',
                        'bg-[var(--hs-info-50)] text-[var(--hs-info-700)]' => $type === 'new_message',
                        'bg-[var(--hs-success-50)] text-[var(--hs-success-700)]' => in_array($type, ['quotation_submitted', 'quotation_awarded']),
                        'bg-[var(--hs-warning-50)] text-[var(--hs-warning-700)]' => in_array($type, ['rfq_received', 'certificate_expiring']),
                        'bg-[var(--hs-danger-50)] text-[var(--hs-danger-700)]' => in_array($type, ['quotation_rejected', 'quotation_withdrawn', 'certificate_expired']),
                        'bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-600)]' => !in_array($type, ['new_message', 'quotation_submitted', 'quotation_awarded', 'rfq_received', 'certificate_expiring', 'quotation_rejected', 'quotation_withdrawn', 'certificate_expired']),
                    ])>
                        @if ($type === 'new_message')
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                        @elseif (in_array($type, ['quotation_submitted', 'quotation_awarded']))
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        @elseif (in_array($type, ['rfq_received']))
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15" /></svg>
                        @else
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <p @class([
                            'text-sm text-[var(--hs-neutral-800)] truncate',
                            'font-semibold' => $isUnread,
                            'font-normal' => !$isUnread,
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
                <div class="px-4 py-8 text-center text-sm text-[var(--hs-neutral-500)]">
                    {{ __('No notifications yet.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
