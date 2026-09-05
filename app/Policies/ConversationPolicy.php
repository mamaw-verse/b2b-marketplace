<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

final class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        if (! config('marketplace.features.chat_enabled')) {
            return false;
        }

        return $conversation->hasParticipant($user);
    }

    public function createThread(User $user, Conversation $conversation): bool
    {
        if (! config('marketplace.features.chat_enabled')) {
            return false;
        }

        return $conversation->hasParticipant($user);
    }
}
