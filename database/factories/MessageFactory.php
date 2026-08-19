<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ConversationThread;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
final class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'thread_id' => ConversationThread::factory(),
            'sender_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
