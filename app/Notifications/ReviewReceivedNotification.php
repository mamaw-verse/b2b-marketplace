<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReviewReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Review $review,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reviewableName = $this->resolveReviewableName();
        $rating = $this->review->rating;
        $body = $this->review->title
            ? "\"{$this->review->title}\" — "
            : '';
        $body .= "A buyer left a {$rating}-star review on {$reviewableName}.";

        return (new MailMessage)
            ->subject("New {$rating}★ Review on {$reviewableName}")
            ->line($body)
            ->action('View Reviews', url('/hub/reviews'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $reviewableName = $this->resolveReviewableName();

        return [
            'type' => 'review_received',
            'review_id' => $this->review->id,
            'rating' => $this->review->rating,
            'reviewable_type' => $this->review->reviewable_type,
            'reviewable_id' => $this->review->reviewable_id,
            'message' => "A buyer left a {$this->review->rating}-star review on {$reviewableName}.",
            'url' => url('/hub/reviews'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    private function resolveReviewableName(): string
    {
        $reviewable = $this->review->reviewable;

        return match (true) {
            $reviewable instanceof Shop => $reviewable->storefront_name,
            $reviewable instanceof Product => $reviewable->name,
            default => 'your listing',
        };
    }
}
