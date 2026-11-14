<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quotation;
use App\Models\RfqRecipient;
use App\Models\User;

final class QuotationPolicy
{
    public function createOrder(User $user, Quotation $quotation): bool
    {
        if (! config('marketplace.features.orders_enabled') || ! config('marketplace.features.quotations_enabled')) {
            return false;
        }

        return $quotation->rfqRecipient->shop->isManagedBy($user)
            && $quotation->status === 'awarded';
    }

    /**
     * Seller can create a quotation if their shop matches the rfq_recipient's shop.
     */
    public function create(User $user, RfqRecipient $rfqRecipient): bool
    {
        if (! config('marketplace.features.quotations_enabled')) {
            return false;
        }

        return $rfqRecipient->shop->isManagedBy($user);
    }

    /**
     * Seller can update a quotation if they own it and it's still a draft.
     */
    public function update(User $user, Quotation $quotation): bool
    {
        if (! config('marketplace.features.quotations_enabled')) {
            return false;
        }

        return $quotation->rfqRecipient->shop->isManagedBy($user)
            && $quotation->isDraft();
    }

    /**
     * Seller can delete a quotation if they own it and it's still a draft.
     */
    public function delete(User $user, Quotation $quotation): bool
    {
        if (! config('marketplace.features.quotations_enabled')) {
            return false;
        }

        return $quotation->rfqRecipient->shop->isManagedBy($user)
            && $quotation->isDraft();
    }

    /**
     * Seller can submit a quotation if they own it, it's a draft, and has ≥1 item.
     */
    public function submit(User $user, Quotation $quotation): bool
    {
        if (! config('marketplace.features.quotations_enabled')) {
            return false;
        }

        return $quotation->rfqRecipient->shop->isManagedBy($user)
            && $quotation->isDraft()
            && $quotation->items()->count() > 0;
    }

    /**
     * Seller can withdraw a submitted quotation they own.
     */
    public function withdraw(User $user, Quotation $quotation): bool
    {
        if (! config('marketplace.features.quotations_enabled')) {
            return false;
        }

        return $quotation->rfqRecipient->shop->isManagedBy($user)
            && $quotation->isSubmitted();
    }

    /**
     * Buyer can award a submitted quotation on their RFQ.
     */
    public function award(User $user, Quotation $quotation): bool
    {
        if (! config('marketplace.features.quotations_enabled')) {
            return false;
        }

        return $user->id === $quotation->rfq->user_id
            && $quotation->isSubmitted();
    }

    /**
     * Buyer can reject a submitted quotation on their RFQ.
     */
    public function reject(User $user, Quotation $quotation): bool
    {
        if (! config('marketplace.features.quotations_enabled')) {
            return false;
        }

        return $user->id === $quotation->rfq->user_id
            && $quotation->isSubmitted();
    }

    /**
     * Buyer can view submitted+ quotations on their RFQ; seller can view their own.
     */
    public function view(User $user, Quotation $quotation): bool
    {
        if (! config('marketplace.features.quotations_enabled')) {
            return false;
        }

        // Seller owns it
        if ($quotation->rfqRecipient->shop->isManagedBy($user)) {
            return true;
        }

        // Buyer owns the parent RFQ and quotation is not a draft
        return $user->id === $quotation->rfq->user_id
            && ! $quotation->isDraft();
    }
}
