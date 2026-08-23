<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Fulfilment;
use App\Models\Order;
use App\Models\User;

final class FulfilmentPolicy
{
    /**
     * Seller can create a fulfilment if they own the order's shop,
     * order is paid, and no fulfilment yet exists.
     */
    public function create(User $user, Order $order): bool
    {
        return $user->id === $order->shop->user_id
            && $order->status === 'paid'
            && ! $order->fulfilments()->exists();
    }

    /**
     * Seller can update fulfilment tracking while status is pending or shipped.
     */
    public function update(User $user, Fulfilment $fulfilment): bool
    {
        return $user->id === $fulfilment->order->shop->user_id
            && in_array($fulfilment->status, ['pending', 'shipped']);
    }

    /**
     * Seller can mark fulfilment as shipped if status is pending.
     */
    public function ship(User $user, Fulfilment $fulfilment): bool
    {
        return $user->id === $fulfilment->order->shop->user_id
            && $fulfilment->status === 'pending';
    }

    /**
     * Seller can mark fulfilment as delivered if status is shipped.
     */
    public function deliver(User $user, Fulfilment $fulfilment): bool
    {
        return $user->id === $fulfilment->order->shop->user_id
            && $fulfilment->status === 'shipped';
    }

    /**
     * Seller can mark fulfilment as returned to seller if status is shipped.
     */
    public function returnToSeller(User $user, Fulfilment $fulfilment): bool
    {
        return $user->id === $fulfilment->order->shop->user_id
            && $fulfilment->status === 'shipped';
    }
}
