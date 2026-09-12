<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Register all of the broadcast channel authorization callbacks.
| Private channel: user.{id} — only the owning user can listen.
| Private channel: conversation.{id} — only participants can listen.
|
*/

Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

Broadcast::channel('conversation.{id}', function (User $user, int $id) {
    $conversation = Conversation::findOrFail($id);

    return $conversation->hasParticipant($user);
});

Broadcast::channel('order.{id}', function (User $user, int $id) {
    $order = Order::findOrFail($id);

    return $user->id === $order->buyer_user_id
        || $user->id === $order->shop->user_id;
});
