<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationThread;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Messages')]
abstract class ChatInbox extends Component
{
    /** The portal layout context: 'account' (buyer) or 'hub' (seller). */
    public string $portal = 'hub';

    public ?int $selectedConversationId = null;

    public ?int $selectedThreadId = null;

    public string $newMessage = '';

    #[Computed]
    final public function conversations()
    {
        return Conversation::forUser(Auth::user())
            ->with(['buyer.buyerProfile', 'shop'])
            ->orderByDesc('updated_at')
            ->get();
    }

    #[Computed]
    final public function threads()
    {
        if (! $this->selectedConversationId) {
            return collect();
        }

        return ConversationThread::where('conversation_id', $this->selectedConversationId)
            ->with(['rfq:id,title,status', 'order'])
            ->orderByDesc('updated_at')
            ->get();
    }

    #[Computed]
    final public function messages()
    {
        if (! $this->selectedThreadId) {
            return collect();
        }

        return Message::where('thread_id', $this->selectedThreadId)
            ->with(['sender', 'thread.rfq'])
            ->orderBy('created_at')
            ->get();
    }

    final public function selectConversation(int $id): void
    {
        $conversation = Conversation::findOrFail($id);
        Gate::authorize('view', $conversation);
        $this->selectedConversationId = $id;
        $this->selectedThreadId = null;

        // Mark as read
        $conversation->markAsReadFor(Auth::user());
    }

    final public function selectThread(int $id): void
    {
        $thread = ConversationThread::findOrFail($id);
        Gate::authorize('view', $thread->conversation);
        $this->selectedThreadId = $id;
    }

    final public function sendMessage(): void
    {
        if (! $this->selectedThreadId || ! mb_trim($this->newMessage)) {
            return;
        }

        $thread = ConversationThread::findOrFail($this->selectedThreadId);
        Gate::authorize('view', $thread->conversation);

        $message = $thread->messages()->create([
            'sender_id' => Auth::id(),
            'body' => mb_trim($this->newMessage),
        ]);

        $thread->conversation->touch();

        if ($thread->rfq_id) {
            $thread->rfq->touchActivity();
            $thread->rfq->recipients()->update(['last_activity_at' => now()]);
        }

        MessageSent::dispatch($message->load('sender'));

        $this->newMessage = '';
    }

    /**
     * Dynamic listeners — Echo subscription is handled client-side via Alpine.
     * This remains for any server-dispatched Livewire events.
     */
    final public function getListeners(): array
    {
        return [];
    }

    /**
     * Clears computed message cache so the list re-renders when a broadcast arrives.
     */
    final public function messageReceived(): void
    {
        unset($this->messages);
    }

    final public function render()
    {
        return view('livewire.shared.chat-inbox')
            ->layout('layouts.portal', ['portal' => $this->portal]);
    }
}
