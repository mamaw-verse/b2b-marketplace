<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProductFlaggedNotification extends Notification implements ShouldQueue
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
            ->subject(__('Your Product Has Been Flagged'))
            ->line(__('Your product ":productName" has been flagged by a moderator for review.', ['productName' => $this->product->name]))
            ->line(__('It remains visible on the marketplace while under review. If you believe this is an error, please contact support.'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'product_flagged',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'message' => "Your product \"{$this->product->name}\" has been flagged by a moderator for review.",
            'url' => url('/support'),
        ];
    }
}
