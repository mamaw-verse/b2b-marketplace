<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class PurgeTestVideosCommand extends Command
{
    protected $signature = 'media:purge-videos
        {--prefix= : Storage prefix to purge (defaults to marketplace.video.storage_prefix)}
        {--force : Permanently delete the matching objects and media rows}';

    protected $description = 'Delete test product videos and their S3 objects under a storage prefix';

    public function handle(): int
    {
        $prefix = trim((string) ($this->option('prefix') ?? config('marketplace.video.storage_prefix', 'media')), '/');

        if ($prefix === '') {
            $this->error('A non-empty storage prefix is required.');

            return self::FAILURE;
        }

        $query = Media::query()
            ->where('mime_type', 'like', 'video/%')
            ->where('storage_path', 'like', $prefix.'/%');

        $count = (clone $query)->count();

        if (! $this->option('force')) {
            $this->info("Would delete {$count} video(s) and every object under [{$prefix}].");
            $this->line('Re-run with --force to apply.');

            return self::SUCCESS;
        }

        $deleted = 0;

        $query->chunkById(100, function ($mediaRows) use (&$deleted): void {
            $ids = $mediaRows->pluck('id');
            $paths = $mediaRows->pluck('storage_path')->all();

            Product::query()
                ->whereIn('video_media_id', $ids)
                ->update(['video_media_id' => null]);

            Storage::disk('s3')->delete($paths);
            Media::query()->whereKey($ids)->delete();

            $deleted += $ids->count();
        });

        Storage::disk('s3')->deleteDirectory($prefix);

        $this->info("Deleted {$deleted} video(s) and every object under [{$prefix}].");

        return self::SUCCESS;
    }
}
