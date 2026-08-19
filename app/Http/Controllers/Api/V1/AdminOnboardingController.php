<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ApproveOnboardingAction;
use App\Events\OnboardingStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\ShopOnboarding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminOnboardingController extends Controller
{
    /**
     * GET /api/v1/admin/onboardings — List onboardings filterable by status.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShopOnboarding::with(['user', 'certificates', 'documents']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $onboardings = $query->orderBy('submitted_at', 'desc')->get();

        return response()->json(['data' => $onboardings]);
    }

    /**
     * GET /api/v1/admin/onboardings/{id} — Full detail with certs + documents.
     */
    public function show(ShopOnboarding $onboarding): JsonResponse
    {
        $onboarding->load(['user', 'certificates.media', 'documents']);

        return response()->json(['data' => $onboarding]);
    }

    /**
     * POST /api/v1/admin/onboardings/{id}/approve — Approve onboarding.
     */
    public function approve(ShopOnboarding $onboarding, ApproveOnboardingAction $action): JsonResponse
    {
        $shop = $action->execute($onboarding, request()->user());

        return response()->json([
            'data' => [
                'onboarding_id' => $onboarding->id,
                'shop_id' => $shop->id,
                'status' => 'approved',
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/onboardings/{id}/request-revision — Request revision with notes.
     */
    public function requestRevision(Request $request, ShopOnboarding $onboarding): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'required|string|min:1',
        ]);

        $onboarding->update([
            'status' => 'requires_revision',
            'admin_notes' => $validated['notes'],
            'reviewed_at' => now(),
            'reviewed_by_id' => $request->user()->id,
        ]);

        OnboardingStatusChanged::dispatch($onboarding->fresh());

        return response()->json([
            'data' => [
                'onboarding_id' => $onboarding->id,
                'status' => 'requires_revision',
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/onboardings/{id}/reject — Reject onboarding with notes.
     */
    public function reject(Request $request, ShopOnboarding $onboarding): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'required|string|min:1',
        ]);

        $onboarding->update([
            'status' => 'rejected',
            'admin_notes' => $validated['notes'],
            'reviewed_at' => now(),
            'reviewed_by_id' => $request->user()->id,
        ]);

        OnboardingStatusChanged::dispatch($onboarding->fresh());

        return response()->json([
            'data' => [
                'onboarding_id' => $onboarding->id,
                'status' => 'rejected',
            ],
        ]);
    }
}
