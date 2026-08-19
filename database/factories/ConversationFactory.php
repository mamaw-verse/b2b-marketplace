<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
final class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buyer_user_id' => User::factory(),
            'shop_id' => Shop::factory(),
        ];
    }
}
