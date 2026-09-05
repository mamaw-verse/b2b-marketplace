<div
    class="flex h-[calc(100vh-9rem)] min-h-[560px] w-full overflow-hidden rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] shadow-[var(--hs-shadow-sm)]"
>
    {{-- CONVERSATIONS SIDEBAR --}}
    <aside class="flex w-[280px] shrink-0 min-h-0 flex-col border-r border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
        <div class="shrink-0 px-4 pb-3 pt-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-[var(--hs-neutral-900)]">{{ __('Messages') }}</h2>
                    <p class="mt-0.5 text-[11px] text-[var(--hs-neutral-500)]">{{ __('Your conversations') }}</p>
                </div>
                @if ($this->conversations->count())
                    <span class="flex size-6 items-center justify-center rounded-full bg-[var(--hs-green-50)] text-[10px] font-bold text-[var(--hs-green-700)]">
                        {{ $this->conversations->count() }}
                    </span>
                @endif
            </div>
            <div class="relative mt-3">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-3.5 -translate-y-1/2 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" />
                </svg>
                <input type="search" placeholder="{{ __('Search...') }}" class="h-9 w-full rounded-lg border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] pl-9 pr-3 text-xs text-[var(--hs-neutral-900)] placeholder:text-[var(--hs-neutral-400)] outline-none transition focus:border-[var(--hs-green-500)] focus:ring-2 focus:ring-[var(--hs-green-100)]" />
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto border-t border-[var(--hs-neutral-100)] [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-[var(--hs-neutral-200)] hover:[&::-webkit-scrollbar-thumb]:bg-[var(--hs-neutral-300)]">
            @forelse ($this->conversations as $conversation)
                @php
                    if ($portal === 'hub') {
                        $displayName = $conversation->buyer->buyerProfile?->business_name
                            ?: trim($conversation->buyer->first_name . ' ' . $conversation->buyer->last_name);
                    } else {
                        $displayName = $conversation->shop->storefront_name;
                    }
                    $initials = strtoupper(mb_substr($displayName, 0, 2));
                    $isActive = $selectedConversationId === $conversation->id;
                    $unreadCount = $conversation->unreadCountFor(auth()->user());
                @endphp
                <button
                    type="button"
                    wire:click="selectConversation({{ $conversation->id }})"
                    class="{{ $isActive ? 'bg-[var(--hs-green-50)]' : 'hover:bg-[var(--hs-neutral-50)]' }} relative flex w-full items-center gap-3 border-b border-[var(--hs-neutral-100)] px-4 py-3 text-left transition"
                >
                    @if ($isActive)
                        <span class="absolute inset-y-0 left-0 w-[3px] bg-[var(--hs-green-600)]"></span>
                    @endif
                    <div class="{{ $isActive ? 'bg-[var(--hs-green-600)] text-white' : 'bg-[var(--hs-neutral-100)] text-[var(--hs-neutral-600)]' }} flex size-9 shrink-0 items-center justify-center rounded-full text-[11px] font-bold">
                        {{ $initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="{{ $isActive ? 'font-bold text-[var(--hs-neutral-900)]' : 'font-semibold text-[var(--hs-neutral-800)]' }} truncate text-xs leading-tight">
                                {{ $displayName }}
                            </p>
                            <span class="shrink-0 text-[10px] font-medium text-[var(--hs-neutral-400)]">
                                {{ $conversation->updated_at->diffForHumans(null, true) }}
                            </span>
                        </div>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <p class="truncate text-[11px] leading-tight text-[var(--hs-neutral-500)]">{{ __('Conversation') }}</p>
                            @if ($unreadCount > 0 && !$isActive)
                                <span class="flex h-4 min-w-[16px] shrink-0 items-center justify-center rounded-full bg-[var(--hs-green-600)] px-1 text-[9px] font-bold text-white">
                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                            @endif
                        </div>
                    </div>
                </button>
            @empty
                <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-[var(--hs-neutral-100)]">
                        <svg class="size-5 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75h.008v.008h-.008V9.75Zm3.375 0h.008v.008H12V9.75Zm3.375 0h.008v.008H15.375V9.75ZM21 12c0 4.142-4.03 7.5-9 7.5-1.07 0-2.097-.145-3.045-.414L5.25 21l.735-3.705C4.125 15.93 3 14.08 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z" />
                        </svg>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-[var(--hs-neutral-700)]">{{ __('No conversations yet') }}</p>
                </div>
            @endforelse
        </div>
    </aside>

    {{-- CHAT AREA --}}
    <section class="flex min-w-0 min-h-0 flex-1 flex-col bg-[var(--hs-neutral-50)]">
        @if ($selectedConversationId)
            @php
                $selectedConversation = $this->conversations->firstWhere('id', $selectedConversationId);
                if ($portal === 'hub') {
                    $displayName = $selectedConversation?->buyer?->buyerProfile?->business_name
                        ?: trim(($selectedConversation?->buyer?->first_name ?? '') . ' ' . ($selectedConversation?->buyer?->last_name ?? ''));
                } else {
                    $displayName = $selectedConversation?->shop?->storefront_name ?? '';
                }
                $initials = strtoupper(mb_substr($displayName, 0, 2));
            @endphp

            {{-- Chat Header --}}
            <header class="shrink-0 border-b border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)]">
                <div class="flex h-14 items-center justify-between px-5">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-[11px] font-bold text-white shadow-sm">
                            {{ $initials }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h1 class="truncate text-sm font-bold text-[var(--hs-neutral-900)]">{{ $displayName }}</h1>
                                <span class="size-1.5 shrink-0 rounded-full bg-[var(--hs-green-500)]"></span>
                            </div>
                            <p class="text-[11px] text-[var(--hs-neutral-500)]">{{ __('Active conversation') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Thread Tabs (redesigned: pinned General, limited visible, More dropdown) --}}
                @if ($this->threads->count())
                    @php
                        $generalThread = $this->threads->firstWhere('is_default', true);
                        $rfqThreads = $this->threads->filter(fn ($t) => $t->rfq_id && ! $t->is_default);
                        $orderThreads = $this->threads->filter(fn ($t) => $t->order_id && ! $t->is_default);

                        // Visible order tabs: up to 2 most recent active (non-completed) orders, then completed
                        $activeOrderThreads = $orderThreads->filter(fn ($t) => $t->order && ! in_array($t->order->status, ['completed', 'refunded', 'cancelled']));
                        $closedOrderThreads = $orderThreads->filter(fn ($t) => $t->order && in_array($t->order->status, ['completed', 'refunded', 'cancelled']));

                        $visibleTabs = collect();
                        if ($generalThread) $visibleTabs->push($generalThread);
                        $visibleTabs = $visibleTabs->merge($activeOrderThreads->take(2));
                        $visibleTabs = $visibleTabs->merge($rfqThreads->take(1));

                        // Overflow: remaining threads
                        $overflowTabs = $this->threads->reject(fn ($t) => $visibleTabs->contains('id', $t->id));
                    @endphp

                    <div class="flex h-10 items-end px-5 border-b border-[var(--hs-neutral-100)]">
                        {{-- Visible tabs --}}
                        @foreach ($visibleTabs as $thread)
                            @php
                                $isThreadActive = $selectedThreadId === $thread->id;
                                $orderForTab = $thread->order;
                                $needsAttention = $orderForTab && in_array($orderForTab->status, ['pending_payment', 'paid']);
                                $isResolved = $orderForTab && in_array($orderForTab->status, ['completed', 'refunded', 'cancelled']);
                            @endphp
                            <button
                                type="button"
                                wire:click="selectThread({{ $thread->id }})"
                                class="{{ $isThreadActive ? 'border-[var(--hs-green-600)] text-[var(--hs-green-700)]' : 'border-transparent text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-800)]' }} flex h-10 shrink-0 items-center gap-1.5 border-b-2 px-3 text-xs font-semibold transition"
                            >
                                @if ($orderForTab && $needsAttention)
                                    <span class="size-1.5 rounded-full bg-[var(--hs-warning-500)]"></span>
                                @elseif ($orderForTab && $isResolved)
                                    <svg class="size-3 text-[var(--hs-green-700)]" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                @elseif ($thread->rfq_id)
                                    <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                @endif
                                <span class="truncate max-w-[120px]">{{ $thread->title ?? __('General') }}</span>
                            </button>
                        @endforeach

                        {{-- More dropdown --}}
                        @if ($overflowTabs->isNotEmpty())
                            <div class="relative ml-1" x-data="{ moreOpen: false }">
                                <button type="button" @click="moreOpen = !moreOpen" class="flex h-10 items-center gap-1 px-3 text-xs font-semibold text-[var(--hs-neutral-500)] hover:text-[var(--hs-neutral-800)] border-b-2 border-transparent">
                                    {{ __('More') }} <span class="text-[9px]">▾</span>
                                </button>
                                <div x-show="moreOpen" @click.outside="moreOpen = false" x-cloak
                                     class="absolute top-10 left-0 z-20 min-w-[220px] rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-1.5 shadow-lg">
                                    @foreach ($overflowTabs as $thread)
                                        @php
                                            $isClosed = $thread->order && in_array($thread->order->status, ['completed', 'refunded', 'cancelled']);
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="selectThread({{ $thread->id }})"
                                            @click="moreOpen = false"
                                            class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-left text-xs font-medium {{ $isClosed ? 'text-[var(--hs-neutral-400)]' : 'text-[var(--hs-neutral-700)]' }} hover:bg-[var(--hs-neutral-50)]"
                                        >
                                            <span class="truncate">
                                                @if ($isClosed) ✓ @endif
                                                {{ $thread->title }}
                                            </span>
                                            @if ($thread->order)
                                                <span class="text-[10px] text-[var(--hs-neutral-400)]">{{ $thread->order->created_at->format('M d') }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Order summary strip (visible only when an order tab is active) --}}
                    @if ($selectedThreadId)
                        @php
                            $activeThread = $this->threads->firstWhere('id', $selectedThreadId);
                            $stripOrder = $activeThread?->order;
                        @endphp
                        @if ($stripOrder)
                            <div class="flex items-center gap-3 px-5 py-2.5 bg-[var(--hs-green-50)] border-b border-[var(--hs-neutral-200)]">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-[var(--hs-neutral-900)] truncate">
                                        Order #{{ $stripOrder->order_number }} · {{ $stripOrder->items()->first()?->description ?? 'Order' }}
                                    </p>
                                    <p class="text-[11px] text-[var(--hs-neutral-500)] truncate">
                                        {{ $stripOrder->items()->count() }} {{ Str::plural('item', $stripOrder->items()->count()) }} · {{ $stripOrder->currency }} {{ number_format((float) $stripOrder->order_total, 2) }}
                                    </p>
                                </div>
                                <x-hs-status-pill :status="$stripOrder->status" />
                                <a href="{{ $portal === 'hub' ? route('hub.orders.show', $stripOrder) : route('account.orders.show', $stripOrder) }}" wire:navigate class="text-[11px] font-semibold text-[var(--hs-green-700)] hover:underline shrink-0">
                                    {{ __('View order →') }}
                                </a>
                            </div>
                        @endif
                    @endif
                @endif
            </header>

            {{-- Messages --}}
            @if ($selectedThreadId)
                <div
                    x-data="{ channel: null }"
                    x-ref="messages"
                    x-init="
                        $nextTick(() => $el.scrollTop = $el.scrollHeight);
                        if (window.Echo) {
                            channel = window.Echo.private('conversation.{{ $selectedConversationId }}');
                            channel.listen('.MessageSent', (e) => {
                                $wire.messageReceived();
                                $nextTick(() => $el.scrollTop = $el.scrollHeight);
                            });
                        }
                    "
                    x-on:livewire-update.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                    x-destroy="if (channel) { channel.stopListening('.MessageSent'); window.Echo.leave('conversation.{{ $selectedConversationId }}'); }"
                    class="min-h-0 flex-1 overflow-y-auto px-5 py-4 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-[var(--hs-neutral-200)] hover:[&::-webkit-scrollbar-thumb]:bg-[var(--hs-neutral-300)]"
                >
                    <div class="mx-auto flex w-full max-w-3xl flex-col gap-1.5">
                        @forelse ($this->messages as $message)
                            @php
                                $isOwnMessage = $message->sender_id === auth()->id();
                                $previous = $loop->index > 0 ? $this->messages[$loop->index - 1] : null;
                                $next = $loop->remaining > 0 ? $this->messages[$loop->index + 1] : null;
                                $isGrouped = $previous && $previous->sender_id === $message->sender_id;
                                $isLastInGroup = !($next && $next->sender_id === $message->sender_id);
                                $extraBottomGap = $isLastInGroup ? 'mb-3' : 'mb-0';
                                $isSystemMessage = str_starts_with($message->body, 'Request for Quotation opened:')
                                    || str_starts_with($message->body, 'Quotation submitted:')
                                    || preg_match('/^order:\d+$/', $message->body)
                                    || preg_match('/^dispute:\d+$/', $message->body);
                            @endphp

                            @if ($isSystemMessage)
                                {{-- System message rendered as a card --}}
                                <div class="flex w-full justify-center my-4">
                                    @if (str_starts_with($message->body, 'Request for Quotation opened:') && $message->thread->rfq)
                                        <x-shared.rfq-card :rfq="$message->thread->rfq" />
                                    @elseif (preg_match('/^order:(\d+)$/', $message->body, $m))
                                        @php $orderCard = \App\Models\Order::with('items')->find((int) $m[1]); @endphp
                                        @if ($orderCard)
                                            <x-shared.order-card :order="$orderCard" />
                                        @endif
                                    @elseif (preg_match('/^dispute:(\d+)$/', $message->body, $m))
                                        @php $disputeCard = \App\Models\Dispute::find((int) $m[1]); @endphp
                                        @if ($disputeCard)
                                            <x-shared.dispute-card :dispute="$disputeCard" />
                                        @endif
                                    @else
                                        <div class="rounded-full bg-[var(--hs-neutral-200)] px-3.5 py-1.5 shadow-sm">
                                            <p class="text-[11px] font-medium text-[var(--hs-neutral-600)]">{{ $message->body }}</p>
                                        </div>
                                    @endif
                                </div>
                            @else

                            <div class="flex w-full {{ $isOwnMessage ? 'justify-end' : 'justify-start' }} {{ $extraBottomGap }}">
                                <div class="flex flex-col {{ $isOwnMessage ? 'items-end' : 'items-start' }} max-w-[70%]">
                                    
                                    @unless ($isGrouped)
                                        <div class="flex items-center gap-1.5 mb-1 px-1">
                                            <span class="text-[10px] font-bold text-[var(--hs-neutral-700)]">
                                                {{ $isOwnMessage ? __('You') : $message->sender->first_name }}
                                            </span>
                                            <span class="text-[9px] font-medium text-[var(--hs-neutral-400)]">
                                                {{ $message->created_at->format('H:i') }}
                                            </span>
                                        </div>
                                    @endunless

                                    <div class="{{ $isOwnMessage
                                            ? 'bg-[var(--hs-green-600)] text-white ' . ($isLastInGroup ? 'rounded-br-sm' : '')
                                            : 'bg-[var(--hs-neutral-0)] border border-[var(--hs-neutral-200)] text-[var(--hs-neutral-800)] ' . ($isLastInGroup ? 'rounded-bl-sm' : '') }}
                                        inline-flex w-fit rounded-2xl px-3.5 py-2 text-left shadow-sm">
                                        <p class="whitespace-pre-wrap break-words text-[13px] leading-relaxed">{{ $message->body }}</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @empty
                            <div class="flex min-h-[280px] flex-1 flex-col items-center justify-center text-center">
                                <div class="flex size-12 items-center justify-center rounded-full bg-[var(--hs-green-50)]">
                                    <svg class="size-6 text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75h.008v.008h-.008V9.75Zm3.375 0h.008v.008H12V9.75Zm3.375 0h.008v.008H15.375V9.75ZM21 12c0 4.142-4.03 7.5-9 7.5-1.07 0-2.097-.145-3.045-.414L5.25 21l.735-3.705C4.125 15.93 3 14.08 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-3 text-sm font-semibold text-[var(--hs-neutral-700)]">{{ __('No messages yet') }}</h3>
                                <p class="mt-1 text-xs text-[var(--hs-neutral-400)]">{{ __('Send a message to start the conversation.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Composer --}}
                <div class="shrink-0 border-t border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] px-5 py-3">
                    <form
                        wire:submit.prevent="sendMessage"
                        x-data="{ 
                            content: @entangle('newMessage'), 
                            resize(el) { 
                                el.style.height = 'auto'; 
                                el.style.height = Math.min(el.scrollHeight, 120) + 'px'; 
                            },
                            submitMessage() {
                                if (this.content && this.content.trim() !== '') {
                                    $wire.sendMessage();
                                    $nextTick(() => this.resize($refs.textarea));
                                }
                            }
                        }"
                        class="mx-auto flex w-full max-w-3xl items-end gap-2.5"
                    >
                        <textarea
                            x-ref="textarea"
                            x-model="content"
                            rows="1"
                            x-init="resize($el)"
                            x-on:input="resize($el)"
                            x-on:keydown.enter.prevent="if (!$event.shiftKey) { submitMessage(); }"
                            placeholder="{{ __('Write a message...') }}"
                            class="max-h-[120px] min-h-[40px] w-full flex-1 resize-none overflow-y-auto rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] px-3.5 py-2.5 text-sm leading-5 text-[var(--hs-neutral-900)] placeholder:text-[var(--hs-neutral-400)] outline-none transition focus:border-[var(--hs-green-500)] focus:bg-[var(--hs-neutral-0)] focus:ring-2 focus:ring-[var(--hs-green-100)] [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
                        ></textarea>
                        <button
                            type="button"
                            x-on:click="submitMessage()"
                            x-bind:disabled="!content || content.trim() === ''"
                            class="flex h-10 shrink-0 items-center gap-1.5 rounded-xl bg-[var(--hs-green-600)] px-4 text-xs font-bold text-white transition hover:bg-[var(--hs-green-700)] focus:outline-none focus:ring-2 focus:ring-[var(--hs-green-200)] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-[var(--hs-green-600)]"
                        >
                            {{ __('Send') }}
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex min-h-0 flex-1 flex-col items-center justify-center px-6 text-center">
                    <div class="flex size-12 items-center justify-center rounded-full bg-[var(--hs-neutral-100)]">
                        <svg class="size-6 text-[var(--hs-neutral-400)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75h.008v.008h-.008V9.75Zm3.375 0h.008v.008H12V9.75Zm3.375 0h.008v.008H15.375V9.75ZM21 12c0 4.142-4.03 7.5-9 7.5-1.07 0-2.097-.145-3.045-.414L5.25 21l.735-3.705C4.125 15.93 3 14.08 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('Select a topic') }}</h3>
                    <p class="mt-1 max-w-xs text-xs text-[var(--hs-neutral-500)]">{{ __('Choose a discussion above to view its messages.') }}</p>
                </div>
            @endif
        @else
            <div class="flex min-h-0 flex-1 flex-col items-center justify-center px-6 text-center">
                <div class="flex size-14 items-center justify-center rounded-full bg-[var(--hs-green-50)]">
                    <svg class="size-7 text-[var(--hs-green-600)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75h.008v.008h-.008V9.75Zm3.375 0h.008v.008H12V9.75Zm3.375 0h.008v.008H15.375V9.75ZM21 12c0 4.142-4.03 7.5-9 7.5-1.07 0-2.097-.145-3.045-.414L5.25 21l.735-3.705C4.125 15.93 3 14.08 3 12c0-4.142 4.03-7.5 9-7.5s9 3.358 9 7.5Z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-[var(--hs-neutral-800)]">{{ __('Select a conversation') }}</h3>
                <p class="mt-1.5 max-w-xs text-xs text-[var(--hs-neutral-500)]">{{ __('Choose a company from the left to view your messages.') }}</p>
            </div>
        @endif
    </section>
</div>