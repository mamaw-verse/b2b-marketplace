<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TransactionController extends Controller
{
    /**
     * List buyer's payment history — successful payments and refunds.
     */
    public function index(Request $request): JsonResponse
    {
        $buyerId = $request->user()->id;

        // Succeeded payments via payment_attempts → order_payment_terms → orders
        $payments = DB::table('payment_attempts')
            ->join('order_payment_terms', 'order_payment_terms.id', '=', 'payment_attempts.order_payment_term_id')
            ->join('orders', 'orders.id', '=', 'order_payment_terms.order_id')
            ->where('orders.buyer_user_id', $buyerId)
            ->where('payment_attempts.status', 'succeeded')
            ->select([
                'payment_attempts.id',
                DB::raw("'payment' as resource_type"),
                DB::raw('CAST(payment_attempts.dlt_amount AS NUMERIC(12,2)) as amount'),
                'orders.id as order_id',
                DB::raw('CAST(NULL AS TEXT) as note'),
                'payment_attempts.succeeded_at as date',
            ]);

        // Refund ledger entries for buyer's orders
        $refunds = DB::table('ledger_entries')
            ->join('orders', 'orders.id', '=', 'ledger_entries.order_id')
            ->where('orders.buyer_user_id', $buyerId)
            ->where('ledger_entries.type', 'refund')
            ->select([
                'ledger_entries.id',
                DB::raw("'refund' as resource_type"),
                'ledger_entries.amount',
                'orders.id as order_id',
                DB::raw('CAST(ledger_entries.note AS TEXT) as note'),
                'ledger_entries.created_at as date',
            ]);

        // Apply filters
        $status = $request->query('status');
        if ($status === 'paid') {
            $query = $payments;
        } elseif ($status === 'refunded') {
            $query = $refunds;
        } else {
            $query = $payments->unionAll($refunds);
        }

        // Build a wrapper query around the union to apply date/amount filters and sorting
        $wrapped = DB::query()->fromSub($query, 'transactions');

        if ($request->filled('from')) {
            $wrapped->where('date', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $wrapped->where('date', '<=', $request->query('to').' 23:59:59');
        }
        if ($request->filled('min_amount')) {
            $wrapped->where('amount', '>=', $request->query('min_amount'));
        }
        if ($request->filled('max_amount')) {
            $wrapped->where('amount', '<=', $request->query('max_amount'));
        }

        $wrapped->orderByDesc('date');

        $results = $wrapped->cursorPaginate($request->integer('limit', 20));

        // Map raw objects into resources
        $results->through(function ($item) {
            $item->date = $item->date ? Carbon::parse($item->date) : null;

            return $item;
        });

        return TransactionResource::collection($results)->response();
    }
}
