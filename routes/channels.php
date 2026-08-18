<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Register all of the broadcast channel authorization callbacks.
| Private channel: user.{id} — only the owning user can listen.
|
*/

Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
