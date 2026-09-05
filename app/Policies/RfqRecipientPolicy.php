<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RfqRecipient;
use App\Models\User;

final class RfqRecipientPolicy
{
    /**
     * Seller can list RFQs received by their shop.
     */
    public function viewAny(User $user): bool
    {
        if (! config('marketplace.features.seller_rfq_queue_enabled')) {
            return false;
        }

        return $user->shop !== null;
    }

    /**
     * Seller can view an RFQ recipient if it belongs to their shop.
     */
    public function view(User $user, RfqRecipient $rfqRecipient): bool
    {
        if (! config('marketplace.features.seller_rfq_queue_enabled')) {
            return false;
        }

        return $user->shop?->id === $rfqRecipient->shop_id;
    }

    /**
     * Seller can transition status if it belongs to their shop and is not terminal.
     */
    public function review(User $user, RfqRecipient $rfqRecipient): bool
    {
        if (! config('marketplace.features.seller_rfq_queue_enabled')) {
            return false;
        }

        return $user->shop?->id === $rfqRecipient->shop_id
            && ! $rfqRecipient->isTerminal();
    }

    /**
     * Seller can mark in_progress if it belongs to their shop and is not terminal.
     */
    public function inProgress(User $user, RfqRecipient $rfqRecipient): bool
    {
        if (! config('marketplace.features.seller_rfq_queue_enabled')) {
            return false;
        }

        return $user->shop?->id === $rfqRecipient->shop_id
            && ! $rfqRecipient->isTerminal();
    }

    /**
     * Seller can close (terminate) their RFQ recipient if it belongs to their shop
     * and is not already in a terminal state.
     */
    public function close(User $user, RfqRecipient $rfqRecipient): bool
    {
        if (! config('marketplace.features.seller_rfq_queue_enabled')) {
            return false;
        }

        return $user->shop?->id === $rfqRecipient->shop_id
            && ! $rfqRecipient->isTerminal();
    }
}
