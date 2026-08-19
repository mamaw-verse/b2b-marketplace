<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuyerProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BuyerProfileController extends Controller
{
    /**
     * Get the authenticated user's buyer profile.
     */
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->buyerProfile;

        if (! $profile) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Buyer profile not found.',
                ],
            ], 404);
        }

        return response()->json(['data' => $profile]);
    }

    /**
     * Create or update the authenticated user's buyer profile.
     */
    public function update(BuyerProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->buyerProfile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        return response()->json(['data' => $profile->fresh()]);
    }
}
