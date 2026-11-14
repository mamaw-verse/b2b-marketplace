<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Browser');

pest()->browser()->timeout(15000);

/**
 * Return a generated sample video path for browser delivery checks.
 * The fixture is generated once via ffmpeg inside the Sail container.
 */
function e2eVideoFixture(): string
{
    $path = base_path('tests/Browser/fixtures/sample.mp4');

    if (is_file($path)) {
        return $path;
    }

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }

    $result = Process::run([
        'ffmpeg', '-y', '-loglevel', 'error',
        '-f', 'lavfi', '-i', 'color=c=black:s=320x240:r=15:d=1',
        '-f', 'lavfi', '-i', 'anullsrc=r=44100:cl=mono',
        '-shortest', '-c:v', 'libx264', '-pix_fmt', 'yuv420p',
        '-c:a', 'aac', '-movflags', '+faststart', $path,
    ]);

    if (! $result->successful() || ! is_file($path)) {
        throw new RuntimeException("Unable to generate the video fixture: {$result->errorOutput()}");
    }

    return $path;
}
