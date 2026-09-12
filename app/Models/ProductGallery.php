<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $product_id
 * @property int $media_id
 * @property int $sort_order
 */
final class ProductGallery extends Model
{
    public $timestamps = false;

    protected $table = 'product_gallery';

    protected $fillable = [
        'product_id',
        'media_id',
        'sort_order',
    ];

    /**
     * The product this gallery image belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The media file for this gallery entry.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the full S3 URL for the gallery image.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->media?->storage_path) {
                return null;
            }

            return str_starts_with($this->media->storage_path, 'http')
                ? $this->media->storage_path
                : Storage::disk('s3')->url($this->media->storage_path);
        });
    }
}
