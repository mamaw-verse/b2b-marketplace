<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RecalculateRatingsJob;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Notifications\ReviewHiddenNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class ReviewReportController extends Controller
{
    /**
     * List pending review reports.
     */
    public function index(): JsonResponse
    {
        Gate::authorize('dismiss', Review::class);

        $reports = ReviewReport::where('status', 'pending')
            ->with(['review.reviewer', 'reporter'])
            ->latest()
            ->cursorPaginate(20);

        return response()->json($reports);
    }

    /**
     * Dismiss a report (no action taken).
     */
    public function dismiss(ReviewReport $reviewReport): JsonResponse
    {
        Gate::authorize('dismiss', Review::class);

        $reviewReport->update([
            'status' => 'dismissed',
            'resolved_by_id' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return response()->json(['message' => 'Report dismissed.']);
    }

    /**
     * Action a report (hide the review).
     */
    public function action(ReviewReport $reviewReport): JsonResponse
    {
        Gate::authorize('hide', Review::class);

        $review = $reviewReport->review;

        $reviewReport->update([
            'status' => 'actioned',
            'resolved_by_id' => auth()->id(),
            'resolved_at' => now(),
        ]);

        $review->update(['is_hidden' => true]);

        // Recalculate ratings since review is now hidden
        RecalculateRatingsJob::dispatch($review->reviewable_type, $review->reviewable_id);

        // Notify the reviewer that their review was hidden
        $review->reviewer->notify(new ReviewHiddenNotification($review));

        return response()->json(['message' => 'Review hidden and report actioned.']);
    }
}
