<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportReviewRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Jobs\RecalculateRatingsJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ReviewController extends Controller
{
    /**
     * Create a review for an order.
     */
    public function store(StoreReviewRequest $request, Order $order, CreateReviewAction $action): JsonResponse
    {
        Gate::authorize('create', [Review::class, $order]);

        $result = $action->execute($order, $request->user(), $request->validated());

        return (new ReviewResource($result['review']->load('reviewer', 'response')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a review.
     */
    public function update(UpdateReviewRequest $request, Review $review): ReviewResource
    {
        Gate::authorize('update', $review);

        $oldRating = $review->rating;

        $review->update($request->validated());

        // Recalculate if rating changed
        if ($oldRating !== $review->rating) {
            RecalculateRatingsJob::dispatch($review->reviewable_type, $review->reviewable_id);
        }

        return new ReviewResource($review->load('reviewer', 'response'));
    }

    /**
     * List reviews for a product (public).
     */
    public function productReviews(Product $product): AnonymousResourceCollection
    {
        $reviews = $product->reviews()
            ->where('is_hidden', false)
            ->with(['reviewer', 'response.responder'])
            ->latest()
            ->cursorPaginate(20);

        return ReviewResource::collection($reviews);
    }

    /**
     * List reviews for a shop (public).
     */
    public function shopReviews(Shop $shop): AnonymousResourceCollection
    {
        $reviews = $shop->reviews()
            ->where('is_hidden', false)
            ->with(['reviewer', 'response.responder'])
            ->latest()
            ->cursorPaginate(20);

        return ReviewResource::collection($reviews);
    }

    /**
     * Report a review.
     */
    public function report(ReportReviewRequest $request, Review $review): JsonResponse
    {
        Gate::authorize('report', $review);

        $report = ReviewReport::create([
            'review_id' => $review->id,
            'reporter_user_id' => $request->user()->id,
            'reason' => $request->validated('reason'),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Review reported successfully.',
            'report_id' => $report->id,
        ], 201);
    }
}
