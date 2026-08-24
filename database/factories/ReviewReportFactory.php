<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewReport>
 */
final class ReviewReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'reporter_user_id' => User::factory(),
            'reason' => fake()->sentence(10),
            'status' => 'pending',
        ];
    }

    /**
     * Report has been dismissed.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dismissed',
            'resolved_by_id' => User::factory(),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Report has been actioned (review hidden).
     */
    public function actioned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'actioned',
            'resolved_by_id' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}
