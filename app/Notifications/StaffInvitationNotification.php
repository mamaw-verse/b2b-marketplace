<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

final class StaffInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Shop $shop, public User $inviter, public string $token)
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('You are invited to join :shop on :app', ['shop' => $this->shop->storefront_name, 'app' => config('app.name')]))
            ->view('emails.staff-invitation', [
                'shop' => $this->shop,
                'inviter' => $this->inviter,
                'url' => route('staff.invitations.show', $this->token),
            ]);
    }
}
