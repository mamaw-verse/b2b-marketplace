<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;

final class DisputePolicy
{
    /**
     * Buyer can create a dispute if they own the order, fulfilment is delivered
     * or returned_to_seller, and no active (non-terminal) dispute already exists.
     */
    public function create(User $user, Order $order): bool
    {
        if ($user->id !== $order->buyer_user_id) {
            return false;
        }

        $fulfilment = $order->fulfilments()->first();
        if (! $fulfilment || ! in_array($fulfilment->status, ['delivered', 'returned_to_seller'])) {
            return false;
        }

        // No active dispute already open
        return ! $order->disputes()
            ->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])
            ->exists();
    }

    /**
     * User can view a dispute if they are the buyer or seller of the order.
     */
    public function view(User $user, Dispute $dispute): bool
    {
        $order = $dispute->order;

        return $user->id === $order->buyer_user_id
            || $user->id === $order->shop->user_id;
    }

    /**
     * Seller can resolve a dispute if they own the order's shop and the dispute
     * is in an appropriate pre-resolution state.
     * - refund_only: status must be 'open'
     * - return_refund: status must be 'return_received'
     */
    public function resolve(User $user, Dispute $dispute): bool
    {
        if ($user->id !== $dispute->order->shop->user_id) {
            return false;
        }

        if ($dispute->type === 'refund_only') {
            return $dispute->status === 'open';
        }

        // return_refund: can only resolve after return is confirmed
        return $dispute->status === 'return_received';
    }

    /**
     * Seller can confirm return if they own the order's shop,
     * dispute type is return_refund, and status is open or awaiting_return.
     */
    public function confirmReturn(User $user, Dispute $dispute): bool
    {
        if ($user->id !== $dispute->order->shop->user_id) {
            return false;
        }

        return $dispute->type === 'return_refund'
            && in_array($dispute->status, ['open', 'awaiting_return']);
    }

    /**
     * Either buyer or seller can escalate if the dispute is not already
     * in a terminal or escalated state.
     */
    public function escalate(User $user, Dispute $dispute): bool
    {
        $order = $dispute->order;
        $isParticipant = $user->id === $order->buyer_user_id
            || $user->id === $order->shop->user_id;

        if (! $isParticipant) {
            return false;
        }

        return ! in_array($dispute->status, [
            'escalated',
            'resolved_refunded',
            'resolved_denied',
            'withdrawn',
        ]);
    }

    /**
     * Buyer can withdraw a dispute they opened if it's not in a terminal state.
     */
    public function withdraw(User $user, Dispute $dispute): bool
    {
        if ($user->id !== $dispute->opened_by_user_id) {
            return false;
        }

        return ! in_array($dispute->status, [
            'resolved_refunded',
            'resolved_denied',
            'withdrawn',
        ]);
    }

    /**
     * Admin can arbitrate an escalated dispute.
     */
    public function arbitrate(User $user, Dispute $dispute): bool
    {
        return $user->hasRole('admin')
            && $dispute->status === 'escalated';
    }
}
