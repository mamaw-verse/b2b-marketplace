<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RfqCreated;
use App\Models\User;
use App\Notifications\RfqSubmittedAdminNotification;

final class NotifyAdminRfqReceived
{
    public function handle(RfqCreated $event): void
    {
        $rfq = $event->rfq;
        $notification = new RfqSubmittedAdminNotification($rfq);

        User::role('admin')->each(function (User $admin) use ($notification): void {
            $admin->notify($notification);
        });
    }
}
