<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class NotificationBell extends Component
{
    /** The authenticated user's ID — used by the Echo channel attribute. */
    public int $userId;

    public function mount(): void
    {
        $this->userId = Auth::id();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        return Auth::user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    public function markRead(string $notificationId): void
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($notificationId);

        $url = $notification->data['url'] ?? null;

        $notification->markAsRead();

        unset($this->notifications, $this->unreadCount);

        if ($url) {
            $this->redirect($url, navigate: false);
        }
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        unset($this->notifications, $this->unreadCount);
    }

    /**
     * Triggered by the JS Echo listener in app.js when a broadcast notification
     * arrives on the user's private channel. Clears cached counts so the badge
     * re-renders immediately.
     */
    #[On('notification-received')]
    public function notificationReceived(): void
    {
        unset($this->notifications, $this->unreadCount);
    }

    public function render()
    {
        return view('livewire.shared.notification-bell');
    }
}
