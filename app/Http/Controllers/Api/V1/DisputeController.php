<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ResolveDisputeAction;
use App\Events\DisputeEscalated;
use App\Events\DisputeOpened;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class DisputeController extends Controller
{
    /**
     * Buyer opens a dispute on a delivered order.
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('create', [Dispute::class, $order]);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:return_refund,refund_only'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $status = $validated['type'] === 'return_refund' ? 'awaiting_return' : 'open';

        $dispute = Dispute::create([
            'order_id' => $order->id,
            'opened_by_user_id' => $request->user()->id,
            'type' => $validated['type'],
            'status' => $status,
            'reason' => $validated['reason'],
        ]);

        DisputeOpened::dispatch($dispute);

        return response()->json([
            'data' => $this->formatDispute($dispute),
        ], 201);
    }

    /**
     * Show dispute detail.
     */
    public function show(Request $request, Order $order, Dispute $dispute): JsonResponse
    {
        Gate::authorize('view', $dispute);

        return response()->json([
            'data' => $this->formatDispute($dispute),
        ]);
    }

    /**
     * Either party escalates a dispute to admin.
     */
    public function escalate(Request $request, Order $order, Dispute $dispute): JsonResponse
    {
        Gate::authorize('escalate', $dispute);

        $dispute->update([
            'status' => 'escalated',
            'escalated_at' => now(),
        ]);

        DisputeEscalated::dispatch($dispute->fresh());

        return response()->json([
            'data' => $this->formatDispute($dispute->fresh()),
        ]);
    }

    /**
     * Buyer withdraws a dispute.
     */
    public function withdraw(Request $request, Order $order, Dispute $dispute): JsonResponse
    {
        Gate::authorize('withdraw', $dispute);

        $dispute->update(['status' => 'withdrawn']);

        return response()->json([
            'data' => $this->formatDispute($dispute->fresh()),
        ]);
    }

    /**
     * Seller resolves a dispute.
     */
    public function resolve(Request $request, Order $order, Dispute $dispute): JsonResponse
    {
        Gate::authorize('resolve', $dispute);

        $validated = $request->validate([
            'resolution_type' => ['required', 'string', 'in:full_refund,partial_refund,denied'],
            'resolution_amount' => ['nullable', 'numeric', 'min:0'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = (new ResolveDisputeAction)->execute($dispute, $request->user(), $validated);

        if (! $result['success']) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => $result['error'],
                ],
            ], 409);
        }

        return response()->json([
            'data' => $this->formatDispute($result['dispute']),
        ]);
    }

    /**
     * Seller confirms physical return received.
     */
    public function confirmReturn(Request $request, Order $order, Dispute $dispute): JsonResponse
    {
        Gate::authorize('confirmReturn', $dispute);

        $dispute->update(['status' => 'return_received']);

        return response()->json([
            'data' => $this->formatDispute($dispute->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDispute(Dispute $dispute): array
    {
        return [
            'id' => $dispute->id,
            'order_id' => $dispute->order_id,
            'type' => $dispute->type,
            'status' => $dispute->status,
            'reason' => $dispute->reason,
            'resolution_type' => $dispute->resolution_type,
            'resolution_amount' => $dispute->resolution_amount,
            'resolution_notes' => $dispute->resolution_notes,
            'resolved_at' => $dispute->resolved_at?->toIso8601String(),
            'escalated_at' => $dispute->escalated_at?->toIso8601String(),
            'created_at' => $dispute->created_at?->toIso8601String(),
        ];
    }
}
