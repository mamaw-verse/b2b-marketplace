<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->message->sender->first_name.' '.$this->message->sender->last_name;

        $url = $notifiable->shop && $notifiable->shop->status === 'active'
            ? url('/hub/messages')
            : url('/account/messages');

        return (new MailMessage)
            ->subject("New message from {$senderName}")
            ->line("{$senderName} sent you a message:")
            ->line('"'.str()->limit($this->message->body, 200).'"')
            ->action('View Conversation', $url);
    }

    public function toArray(object $notifiable): array
    {
        $url = $notifiable->shop && $notifiable->shop->status === 'active'
            ? url('/hub/messages')
            : url('/account/messages');

        return [
            'type' => 'new_message',
            'message_id' => $this->message->id,
            'sender_name' => $this->message->sender->first_name,
            'body_preview' => str()->limit($this->message->body, 100),
            'message' => 'New message from '.$this->message->sender->first_name,
            'url' => $url,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
