<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProductUnpublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Product Has Been Unpublished')
            ->line("Your product \"{$this->product->name}\" has been unpublished by a moderator and removed from search.")
            ->line('If you believe this is an error, please contact support.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'product_unpublished',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'message' => "Your product \"{$this->product->name}\" has been unpublished by a moderator and removed from search.",
            'url' => url('/support'),
        ];
    }
}
