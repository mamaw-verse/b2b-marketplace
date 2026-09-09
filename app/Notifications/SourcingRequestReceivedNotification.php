<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SourcingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SourcingRequestReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SourcingRequest $sourcingRequest,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New product sourcing request from {$this->sourcingRequest->name}")
            ->line("{$this->sourcingRequest->name} is looking for: {$this->sourcingRequest->product_name}")
            ->action('View Sourcing Requests', route('admin.sourcing-requests.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sourcing_request_received',
            'sourcing_request_id' => $this->sourcingRequest->id,
            'requester_name' => $this->sourcingRequest->name,
            'email' => $this->sourcingRequest->email,
            'product_name' => $this->sourcingRequest->product_name,
            'message' => "{$this->sourcingRequest->name} is looking for: {$this->sourcingRequest->product_name}",
            'url' => route('admin.sourcing-requests.index'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
