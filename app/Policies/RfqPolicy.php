<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rfq;
use App\Models\User;

final class RfqPolicy
{
    /**
     * Any authenticated buyer can list their own RFQs.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Buyer can only view their own RFQ.
     */
    public function view(User $user, Rfq $rfq): bool
    {
        return $user->id === $rfq->user_id;
    }

    /**
     * Buyer can create an RFQ if they have a completed buyer profile.
     */
    public function create(User $user): bool
    {
        return $user->buyerProfile !== null;
    }

    /**
     * Buyer can close their own RFQ (status check done in controller for correct HTTP code).
     */
    public function close(User $user, Rfq $rfq): bool
    {
        return $user->id === $rfq->user_id;
    }
}
