<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ShopAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SellerHubController extends Controller
{
    public function __construct(
        private readonly ShopAnalyticsService $analytics,
    ) {}

    /**
     * Dashboard to-do widget counts.
     */
    public function dashboard(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');

        return response()->json([
            'data' => $this->analytics->dashboardCounts($request->user()->activeShop),
        ]);
    }

    /**
     * Customers list (buyers who have transacted with this shop).
     */
    public function customers(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');

        $customers = $this->analytics->customers(
            $request->user()->activeShop,
            $request->integer('limit', 20),
        );

        return response()->json([
            'data' => $customers->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->first_name.' '.$user->last_name,
                'email' => $user->email,
                'order_count' => $user->orders_count,
                'last_order_date' => $user->orders_max_created_at,
            ]),
            'meta' => [
                'next_cursor' => $customers->nextCursor()?->encode(),
                'prev_cursor' => $customers->previousCursor()?->encode(),
                'per_page' => $customers->perPage(),
            ],
        ]);
    }

    /**
     * Sales & fulfilment reports over a time window.
     */
    public function reports(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');

        $windowDays = $request->integer('window', 30);
        $from = $request->date('from') ?? now()->subDays($windowDays);
        $to = $from->copy()->addDays($windowDays);

        return response()->json([
            'data' => $this->analytics->reports($request->user()->activeShop, $windowDays, $from, $to),
        ]);
    }

    /**
     * Leaderboards — top products / customers / categories.
     */
    public function leaderboards(Request $request): JsonResponse
    {
        Gate::authorize('accessHub');

        $data = $this->analytics->leaderboards(
            $request->user()->activeShop,
            $request->query('type', 'products'),
            $request->query('rank_by', 'revenue'),
            $request->integer('limit', 10),
            $request->integer('window', 0),
        );

        return response()->json(['data' => $data]);
    }
}
