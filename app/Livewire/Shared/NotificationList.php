<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal', ['portal' => 'account'])]
#[Title('Notifications')]
final class NotificationList extends Component
{
    use WithPagination;

    /**
     * Paginated notifications for the authenticated user.
     */
    public function notifications(): LengthAwarePaginator
    {
        return Auth::user()
            ->notifications()
            ->latest()
            ->paginate(25);
    }

    public function markRead(string $notificationId): void
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($notificationId);

        $url = $notification->data['url'] ?? null;

        $notification->markAsRead();

        if ($url) {
            $this->redirect($url, navigate: false);
        }
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.shared.notification-list', [
            'notifications' => $this->notifications(),
        ]);
    }
}
