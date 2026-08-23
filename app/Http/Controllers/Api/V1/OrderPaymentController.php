<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\DltPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

final class OrderPaymentController extends Controller
{
    /**
     * Initiate a payment for an order.
     *
     * Creates a payment attempt and returns the DLT hosted checkout URL.
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        // Only the buyer can initiate payment
        if ($request->user()->id !== $order->buyer_user_id) {
            abort(403);
        }

        if ($order->status !== 'pending_payment') {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'This order is not awaiting payment.',
                ],
            ], 409);
        }

        $paymentTerm = $order->paymentTerms()->first();

        if (! $paymentTerm) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'No payment term found for this order.',
                ],
            ], 409);
        }

        // Generate unique merchant transaction ID
        $merchantTxnId = 'order-'.$order->id.'-'.Str::ulid();
        // DLT field max is 50 chars
        $merchantTxnId = Str::limit($merchantTxnId, 50, '');

        // Create payment attempt
        $attempt = PaymentAttempt::create([
            'order_payment_term_id' => $paymentTerm->id,
            'status' => 'pending',
            'dlt_merchant_transaction_id' => $merchantTxnId,
        ]);

        // Build callback URLs
        $successUrl = route('orders.payment.success', [
            'order' => $order->id,
            'attempt' => $attempt->id,
        ]);
        $failureUrl = route('orders.payment.failure', [
            'order' => $order->id,
            'attempt' => $attempt->id,
        ]);

        // Product name for DLT (first item description or generic)
        $productName = $order->items()->first()?->description ?? "Order #{$order->id}";
        $productName = Str::limit($productName, 100, '');

        try {
            $dlt = app(DltPaymentService::class);
            $session = $dlt->createQuickLinkSession(
                $merchantTxnId,
                (float) $order->order_total,
                $productName,
                $successUrl,
                $failureUrl,
            );

            // Store DLT response on the attempt
            $attempt->update([
                'dlt_quick_link_token' => $session['token'],
                'dlt_payment_url' => $session['checkout_url'],
            ]);

            return response()->json([
                'data' => [
                    'payment_attempt_id' => $attempt->id,
                    'checkout_url' => $session['checkout_url'],
                    'expires_at' => $session['expires_at'],
                ],
            ], 201);
        } catch (RuntimeException $e) {
            // Mark attempt as failed if DLT call fails
            $attempt->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_response' => ['error' => $e->getMessage()],
            ]);

            return response()->json([
                'error' => [
                    'code' => 'GATEWAY_ERROR',
                    'message' => 'Payment gateway error. Please try again.',
                ],
            ], 502);
        }
    }
}
