<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ConversationThread;
use App\Models\User;

final class MessagePolicy
{
    /**
     * User can send a message if they are a participant in the thread's conversation.
     */
    public function create(User $user, ConversationThread $thread): bool
    {
        return $thread->conversation->hasParticipant($user);
    }
}
