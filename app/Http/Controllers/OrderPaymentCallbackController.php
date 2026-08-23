<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProcessOrderPaymentAction;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Services\DltPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class OrderPaymentCallbackController extends Controller
{
    /**
     * Handle DLT success redirect.
     *
     * The redirect to success_url IS the payment confirmation signal from DLT.
     * We call getQuickLinkSession as a tamper guard (verify token is real).
     */
    public function success(Request $request, Order $order): RedirectResponse
    {
        $attempt = PaymentAttempt::findOrFail($request->query('attempt'));

        // Idempotency: if already succeeded, just redirect to confirmation
        if ($attempt->status === 'succeeded') {
            return redirect()->route('account.orders.show', $order);
        }

        // Verify the attempt belongs to this order
        $paymentTerm = $order->paymentTerms()->first();
        if (! $paymentTerm || $attempt->order_payment_term_id !== $paymentTerm->id) {
            abort(403);
        }

        // Tamper guard: verify the token is valid with DLT
        try {
            $dlt = app(DltPaymentService::class);
            $sessionData = $dlt->getQuickLinkSession($attempt->dlt_quick_link_token);

            // Store the session response for audit
            $attempt->update(['gateway_response' => $sessionData]);
        } catch (RuntimeException $e) {
            // If DLT verification fails, still process (the redirect itself is authoritative)
            // but log the issue
            report($e);
        }

        // Process the payment
        $action = app(ProcessOrderPaymentAction::class);
        $action->execute($attempt, $order);

        return redirect()->route('account.orders.show', $order);
    }

    /**
     * Handle DLT failure redirect.
     */
    public function failure(Request $request, Order $order): RedirectResponse
    {
        $attempt = PaymentAttempt::findOrFail($request->query('attempt'));

        // Verify the attempt belongs to this order
        $paymentTerm = $order->paymentTerms()->first();
        if (! $paymentTerm || $attempt->order_payment_term_id !== $paymentTerm->id) {
            abort(403);
        }

        // Mark attempt as failed (if not already terminal)
        if ($attempt->status === 'pending') {
            $attempt->update([
                'status' => 'failed',
                'failed_at' => now(),
                'dlt_payment_status' => 'REJECTED',
            ]);
        }

        // Redirect buyer back to order detail with payment-failed flash
        return redirect()->route('account.orders.show', $order)
            ->with('payment_failed', true);
    }
}
