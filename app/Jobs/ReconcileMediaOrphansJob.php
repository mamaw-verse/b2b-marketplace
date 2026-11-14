<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ReconcileMediaOrphansJob implements ShouldQueue
{
    use Queueable;

    /**
     * Every database column that references media.id.
     *
     * @var array<string, list<string>>
     */
    public const array REFERENCES = [
        'shop_onboarding_documents' => ['media_id'],
        'shop_documents' => ['media_id'],
        'product_documents' => ['media_id'],
        'product_gallery' => ['media_id'],
        'shop_onboarding_certificates' => ['media_id'],
        'shop_certificates' => ['media_id'],
        'dispute_media' => ['media_id'],
        'certification_applications' => ['media_id'],
        'quotation_attachments' => ['media_id'],
        'rfq_attachments' => ['media_id'],
        'message_attachments' => ['media_id'],
        'sponsored_content' => ['media_id', 'wide_media_id', 'tall_media_id'],
        'payment_attempts' => ['receipt_media_id'],
        'products' => ['video_media_id'],
    ];

    /**
     * Find orphaned media rows and delete them along with their S3 objects.
     */
    public function handle(): void
    {
        $gracePeriod = Carbon::now()->subDays(7);

        $orphans = Media::query()->where('created_at', '<', $gracePeriod);

        foreach (self::REFERENCES as $table => $columns) {
            foreach ($columns as $column) {
                $orphans->whereNotExists(function ($subquery) use ($table, $column) {
                    $subquery->select(DB::raw(1))
                        ->from($table)
                        ->whereColumn("{$table}.{$column}", 'media.id');
                });
            }
        }

        $orphans
            ->chunk(100, function ($mediaRecords) {
                foreach ($mediaRecords as $media) {
                    Storage::disk('s3')->delete($media->storage_path);
                    $media->delete();
                }
            });
    }
}
