<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(rand(1, 3), true);

        return [
            'parent_id' => null,
            'name' => ucfirst($name),
            'description' => fake()->optional()->sentence(),
            'slug' => Str::slug($name),
        ];
    }

    /**
     * Nest this category under a parent group.
     */
    public function childOf(Category $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
