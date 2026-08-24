<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReviewHiddenNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Review $review,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reviewableName = $this->resolveReviewableName();

        return (new MailMessage)
            ->subject('Your Review Has Been Hidden')
            ->line("Your review on {$reviewableName} has been hidden by a moderator for violating community guidelines. If you believe this is an error, please contact support.");
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $reviewableName = $this->resolveReviewableName();

        return [
            'type' => 'review_hidden',
            'review_id' => $this->review->id,
            'reviewable_type' => $this->review->reviewable_type,
            'reviewable_id' => $this->review->reviewable_id,
            'message' => "Your review on {$reviewableName} has been hidden by a moderator for violating community guidelines.",
            'url' => url('/support'),
        ];
    }

    private function resolveReviewableName(): string
    {
        $reviewable = $this->review->reviewable;

        return match (true) {
            $reviewable instanceof Shop => $reviewable->storefront_name,
            $reviewable instanceof Product => $reviewable->name,
            default => 'a listing',
        };
    }
}
