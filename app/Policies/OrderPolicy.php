<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

final class OrderPolicy
{
    /**
     * Seller can create an order if they have an active shop.
     */
    public function create(User $user): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        return $user->shop !== null && $user->shop->status === 'active';
    }

    /**
     * User can view an order if they are the buyer or the seller's shop owner.
     */
    public function view(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        return $user->id === $order->buyer_user_id
            || $user->id === $order->shop->user_id;
    }

    /**
     * Seller can update a draft order they own.
     */
    public function update(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        return $user->id === $order->shop->user_id
            && $order->status === 'draft';
    }

    /**
     * Seller can submit a draft order they own.
     */
    public function submit(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        return $user->id === $order->shop->user_id
            && $order->status === 'draft';
    }

    /**
     * Seller can recall a pending_payment order they own.
     */
    public function recall(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        return $user->id === $order->shop->user_id
            && $order->status === 'pending_payment';
    }

    /**
     * Buyer can cancel a pending_payment order they own.
     */
    public function cancelAsBuyer(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        return $user->id === $order->buyer_user_id
            && $order->status === 'pending_payment';
    }

    /**
     * Buyer can pay a pending_payment order they own.
     */
    public function pay(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        return $user->id === $order->buyer_user_id
            && $order->status === 'pending_payment';
    }

    /**
     * Seller can cancel a paid order before fulfilment starts.
     * "Before fulfilment starts" = no fulfilment exists, or fulfilment exists with status 'pending' and hasn't shipped.
     */
    public function cancelAsSeller(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        if ($user->id !== $order->shop->user_id) {
            return false;
        }

        if ($order->status !== 'paid') {
            return false;
        }

        // No fulfilment exists, or fulfilment is still pending (not shipped/delivered)
        $fulfilment = $order->fulfilments()->first();

        return $fulfilment === null || $fulfilment->status === 'pending';
    }

    /**
     * Buyer can confirm receipt if fulfilment is delivered and no active dispute.
     */
    public function confirmReceipt(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        if ($user->id !== $order->buyer_user_id) {
            return false;
        }

        $fulfilment = $order->fulfilments()->first();
        if (! $fulfilment || $fulfilment->status !== 'delivered') {
            return false;
        }

        // No active (non-terminal) dispute
        return ! $order->disputes()
            ->whereNotIn('status', ['resolved_refunded', 'resolved_denied', 'withdrawn'])
            ->exists();
    }

    /**
     * Seller can delete a draft order they own.
     */
    public function delete(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        return $user->id === $order->shop->user_id
            && $order->status === 'draft';
    }

    /**
     * Seller who owns the shop can act on a dispute for this order
     * (confirm return, resolve, escalate). Independent of order status.
     */
    public function manageDispute(User $user, Order $order): bool
    {
        if (! config('marketplace.features.orders_enabled')) {
            return false;
        }

        return $user->id === $order->shop->user_id;
    }
}
