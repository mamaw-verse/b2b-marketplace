<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Shop;
use App\Models\ShopStaff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShopStaff> */
final class ShopStaffFactory extends Factory
{
    protected $model = ShopStaff::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'email' => fake()->unique()->safeEmail(),
            'invited_by_id' => User::factory(),
            'status' => 'invited',
            'expires_at' => now()->addDays(7),
        ];
    }

    public function active(?User $user = null): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user?->id ?? User::factory(),
            'status' => 'active',
            'accepted_at' => now(),
            'token_hash' => null,
            'expires_at' => null,
        ]);
    }
}
