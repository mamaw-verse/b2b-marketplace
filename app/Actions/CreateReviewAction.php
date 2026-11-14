<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\IndexProductJob;
use App\Jobs\IndexShopJob;
use App\Jobs\RecalculateRatingsJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\ReviewReceivedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class CreateReviewAction
{
    /**
     * Create a review for an order.
     *
     * @param  array{reviewable_type: string, reviewable_id: int, rating: int, title?: string|null, body?: string|null}  $data
     * @return array{success: bool, review?: Review, error?: string}
     */
    public function execute(Order $order, User $user, array $data): array
    {
        // Validate order is completed
        if ($order->status !== 'completed') {
            throw ValidationException::withMessages([
                'order' => 'You can only review completed orders.',
            ]);
        }

        // Validate user is the buyer
        if ($order->buyer_user_id !== $user->id) {
            throw ValidationException::withMessages([
                'order' => 'You are not authorized to review this order.',
            ]);
        }

        // Resolve reviewable type class
        $reviewableType = match ($data['reviewable_type']) {
            'product' => Product::class,
            'shop' => Shop::class,
            default => throw ValidationException::withMessages([
                'reviewable_type' => 'Invalid reviewable type.',
            ]),
        };

        // Check for duplicate review
        $exists = Review::where('order_id', $order->id)
            ->where('reviewer_user_id', $user->id)
            ->where('reviewable_type', $reviewableType)
            ->where('reviewable_id', $data['reviewable_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'reviewable_id' => 'You have already reviewed this entity for this order.',
            ]);
        }

        $review = Review::create([
            'order_id' => $order->id,
            'reviewer_user_id' => $user->id,
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $data['reviewable_id'],
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
        ]);

        // Dispatch recalculation job
        RecalculateRatingsJob::dispatch($reviewableType, $data['reviewable_id']);

        // Dispatch search index job
        if ($reviewableType === Shop::class) {
            $shop = Shop::find($data['reviewable_id']);
            if ($shop) {
                IndexShopJob::dispatch($shop);
            }
        } elseif ($reviewableType === Product::class) {
            $product = Product::find($data['reviewable_id']);
            if ($product) {
                IndexProductJob::dispatch($product);
            }
        }

        // Notify the shop owner about the new review
        $shop = match ($reviewableType) {
            Shop::class => Shop::find($data['reviewable_id']),
            Product::class => Product::find($data['reviewable_id'])?->shop,
            default => null,
        };

        if ($shop) {
            Notification::send($shop->operationalRecipients(), new ReviewReceivedNotification($review));
        }

        return [
            'success' => true,
            'review' => $review,
        ];
    }
}
