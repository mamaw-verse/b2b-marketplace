<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DisputeEscalated;
use App\Models\User;
use App\Notifications\DisputeEscalatedNotification;

final class NotifyAdminDisputeEscalated
{
    public function handle(DisputeEscalated $event): void
    {
        User::role('admin')->get()->each(fn (User $admin) => $admin->notify(
            new DisputeEscalatedNotification($event->dispute),
        ));
    }
}
