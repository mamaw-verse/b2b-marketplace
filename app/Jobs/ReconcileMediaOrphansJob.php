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
     * Find orphaned media rows and delete them along with their S3 objects.
     */
    public function handle(): void
    {
        $gracePeriod = Carbon::now()->subDays(7);

        Media::query()
            ->where('created_at', '<', $gracePeriod)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('shop_onboarding_documents')
                    ->whereColumn('shop_onboarding_documents.media_id', 'media.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('shop_documents')
                    ->whereColumn('shop_documents.media_id', 'media.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('product_documents')
                    ->whereColumn('product_documents.media_id', 'media.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('product_gallery')
                    ->whereColumn('product_gallery.media_id', 'media.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('shop_onboarding_certificates')
                    ->whereColumn('shop_onboarding_certificates.media_id', 'media.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('shop_certificates')
                    ->whereColumn('shop_certificates.media_id', 'media.id');
            })
            ->chunk(100, function ($mediaRecords) {
                foreach ($mediaRecords as $media) {
                    Storage::disk('s3')->delete($media->storage_path);
                    $media->delete();
                }
            });
    }
}
