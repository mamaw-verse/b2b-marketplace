<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LedgerEntryResource;
use App\Models\LedgerEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LedgerController extends Controller
{
    /**
     * Platform-wide financial ledger for admin.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LedgerEntry::query()
            ->with(['order:id,shop_id,buyer_user_id,order_total,status', 'order.shop:id,storefront_name']);

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('shop_id')) {
            $query->whereHas('order', fn ($q) => $q->where('shop_id', $request->query('shop_id')));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->query('to').' 23:59:59');
        }

        $query->orderByDesc('created_at');

        $entries = $query->cursorPaginate($request->integer('limit', 20));

        return LedgerEntryResource::collection($entries)->response();
    }
}
