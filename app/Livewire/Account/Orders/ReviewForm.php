<?php

declare(strict_types=1);

namespace App\Livewire\Account\Orders;

use App\Actions\CreateReviewAction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

final class ReviewForm extends Component
{
    public Order $order;

    public int $shopRating = 0;

    public string $shopBody = '';

    /** @var array<int, array{rating: int, body: string}> keyed by product_id */
    public array $productReviews = [];

    public bool $showForm = false;

    public ?int $editingReviewId = null;

    public int $editRating = 0;

    public string $editBody = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load(['shop', 'items.variant.product', 'reviews']);
    }

    /**
     * Get existing reviews for this order by the current user.
     *
     * @return Collection<int, Review>
     */
    public function getExistingReviewsProperty(): Collection
    {
        return Review::where('order_id', $this->order->id)
            ->where('reviewer_user_id', auth()->id())
            ->with('response.responder')
            ->get();
    }

    /**
     * Get the distinct products from this order available for review.
     * Deduplicates by product_id — one review per product, not per variant.
     *
     * @return Collection<int, Product>
     */
    public function getReviewableProductsProperty(): Collection
    {
        return $this->order->items
            ->filter(fn ($item) => $item->variant_id && $item->variant?->product_id)
            ->map(fn ($item) => $item->variant->product)
            ->unique('id')
            ->values();
    }

    public function openForm(): void
    {
        $this->showForm = true;

        // Initialize per-product review entries
        foreach ($this->reviewableProducts as $product) {
            if (! isset($this->productReviews[$product->id])) {
                $this->productReviews[$product->id] = ['rating' => 0, 'body' => ''];
            }
        }
    }

    public function submit(CreateReviewAction $action): void
    {
        $user = auth()->user();

        if (Gate::denies('create', [Review::class, $this->order])) {
            $this->addError('form', 'You cannot review this order.');

            return;
        }

        $submitted = false;

        // Submit shop review if rating provided
        if ($this->shopRating > 0) {
            $existing = $this->existingReviews
                ->where('reviewable_type', Shop::class)
                ->where('reviewable_id', $this->order->shop_id)
                ->first();

            if (! $existing) {
                $action->execute($this->order, $user, [
                    'reviewable_type' => 'shop',
                    'reviewable_id' => $this->order->shop_id,
                    'rating' => $this->shopRating,
                    'title' => null,
                    'body' => $this->shopBody ?: null,
                ]);
                $submitted = true;
            }
        }

        // Submit per-product reviews
        foreach ($this->productReviews as $productId => $data) {
            if (($data['rating'] ?? 0) > 0) {
                $existing = $this->existingReviews
                    ->where('reviewable_type', Product::class)
                    ->where('reviewable_id', $productId)
                    ->first();

                if (! $existing) {
                    $action->execute($this->order, $user, [
                        'reviewable_type' => 'product',
                        'reviewable_id' => $productId,
                        'rating' => $data['rating'],
                        'title' => null,
                        'body' => $data['body'] ?: null,
                    ]);
                    $submitted = true;
                }
            }
        }

        if ($submitted) {
            $this->showForm = false;
            unset($this->existingReviews); // clear computed cache
            session()->flash('review-success', 'Your review has been submitted. Thank you!');
        }
    }

    public function editReview(int $reviewId): void
    {
        $review = Review::where('id', $reviewId)
            ->where('reviewer_user_id', auth()->id())
            ->where('order_id', $this->order->id)
            ->firstOrFail();

        $this->editingReviewId = $review->id;
        $this->editRating = $review->rating;
        $this->editBody = $review->body ?? '';
    }

    public function cancelEdit(): void
    {
        $this->editingReviewId = null;
        $this->editRating = 0;
        $this->editBody = '';
    }

    public function updateReview(): void
    {
        $review = Review::where('id', $this->editingReviewId)
            ->where('reviewer_user_id', auth()->id())
            ->where('order_id', $this->order->id)
            ->firstOrFail();

        if ($this->editRating < 1 || $this->editRating > 5) {
            $this->addError('form', 'Rating must be between 1 and 5.');

            return;
        }

        $review->update([
            'rating' => $this->editRating,
            'body' => $this->editBody ?: null,
        ]);

        $this->editingReviewId = null;
        $this->editRating = 0;
        $this->editBody = '';
        unset($this->existingReviews);

        session()->flash('review-success', 'Your review has been updated.');
    }

    public function render(): View
    {
        return view('livewire.account.orders.review-form');
    }
}
