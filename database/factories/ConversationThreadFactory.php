<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationThread>
 */
final class ConversationThreadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'title' => fake()->sentence(3),
            'rfq_id' => null,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'title' => 'General',
            'is_default' => true,
        ]);
    }
}
