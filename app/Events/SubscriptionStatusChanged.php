<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Subscription;

final class SubscriptionStatusChanged
{
    public function __construct(
        public readonly Subscription $subscription,
    ) {}
}
