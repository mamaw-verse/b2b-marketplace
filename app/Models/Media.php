<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $original_filename
 * @property string $extension
 * @property string $storage_path
 * @property string $mime_type
 * @property string|null $description
 * @property string $scan_status
 * @property int $uploaded_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'original_filename',
        'extension',
        'storage_path',
        'mime_type',
        'description',
        'scan_status',
        'uploaded_by_id',
    ];

    /**
     * The user who uploaded this media file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * Products that attach this media as a document.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_documents');
    }

    /**
     * Disputes that attach this media as a supporting photo.
     */
    public function disputes(): BelongsToMany
    {
        return $this->belongsToMany(Dispute::class, 'dispute_media');
    }

    /**
     * RFQs that attach this media as an attachment.
     */
    public function rfqs(): BelongsToMany
    {
        return $this->belongsToMany(Rfq::class, 'rfq_attachments');
    }
}
