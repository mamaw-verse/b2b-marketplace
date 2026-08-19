<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
final class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'jpg', 'png']);
        $filename = fake()->word().'.'.$extension;

        return [
            'original_filename' => $filename,
            'extension' => $extension,
            'storage_path' => 'media/'.fake()->uuid().'/'.$filename,
            'mime_type' => match ($extension) {
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
            },
            'description' => fake()->optional()->randomElement(['halal certificate', 'business permit', 'BIR form', 'product spec']),
            'uploaded_by_id' => User::factory(),
        ];
    }
}
