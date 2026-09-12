<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\Shop;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class ReviewList extends Component
{
    use WithPagination;

    public string $reviewableType;

    public int $reviewableId;

    public ?int $reportingReviewId = null;

    public string $reportReason = '';

    public function mount(string $reviewableType, int $reviewableId): void
    {
        $this->reviewableType = $reviewableType;
        $this->reviewableId = $reviewableId;
    }

    public function openReportModal(int $reviewId): void
    {
        $this->reportingReviewId = $reviewId;
        $this->reportReason = '';
    }

    public function submitReport(): void
    {
        $this->validate([
            'reportReason' => ['required', 'string', 'max:1000'],
        ]);

        $user = auth()->user();

        if (! $user) {
            $this->addError('reportReason', 'You must be logged in to report a review.');

            return;
        }

        $review = Review::find($this->reportingReviewId);

        if (! $review) {
            return;
        }

        // Can't report your own review
        if ($review->reviewer_user_id === $user->id) {
            $this->addError('reportReason', 'You cannot report your own review.');

            return;
        }

        // Check if already reported by this user
        $alreadyReported = ReviewReport::where('review_id', $review->id)
            ->where('reporter_user_id', $user->id)
            ->exists();

        if ($alreadyReported) {
            $this->addError('reportReason', 'You have already reported this review.');

            return;
        }

        ReviewReport::create([
            'review_id' => $review->id,
            'reporter_user_id' => $user->id,
            'reason' => $this->reportReason,
            'status' => 'pending',
        ]);

        $this->reportingReviewId = null;
        $this->reportReason = '';

        session()->flash('review-reported', 'Report submitted. Our team will review it shortly.');
    }

    public function render(): View
    {
        $modelClass = match ($this->reviewableType) {
            'product' => Product::class,
            'shop' => Shop::class,
            default => Shop::class,
        };

        $reviews = Review::where('reviewable_type', $modelClass)
            ->where('reviewable_id', $this->reviewableId)
            ->where('is_hidden', false)
            ->with(['reviewer', 'response.responder'])
            ->latest()
            ->paginate(10);

        return view('livewire.storefront.review-list', [
            'reviews' => $reviews,
        ]);
    }
}
