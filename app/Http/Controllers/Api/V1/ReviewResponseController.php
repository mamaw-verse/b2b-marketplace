<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewResponseRequest;
use App\Http\Resources\ReviewResponseResource;
use App\Models\Review;
use App\Models\ReviewResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class ReviewResponseController extends Controller
{
    /**
     * Seller posts a response to a review.
     */
    public function store(StoreReviewResponseRequest $request, Review $review): JsonResponse
    {
        Gate::authorize('respond', $review);

        $response = ReviewResponse::create([
            'review_id' => $review->id,
            'responder_user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return (new ReviewResponseResource($response->load('responder')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Seller edits their response.
     */
    public function update(StoreReviewResponseRequest $request, ReviewResponse $reviewResponse): ReviewResponseResource
    {
        $review = $reviewResponse->review;
        Gate::authorize('respond', $review);

        $reviewResponse->update([
            'body' => $request->validated('body'),
        ]);

        return new ReviewResponseResource($reviewResponse->load('responder'));
    }
}
