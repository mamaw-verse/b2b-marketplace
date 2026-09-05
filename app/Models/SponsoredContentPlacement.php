<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sponsored_content_id
 * @property string $placement_key
 */
final class SponsoredContentPlacement extends Model
{
    /** No timestamps on this static config join table. */
    public $timestamps = false;

    protected $fillable = [
        'sponsored_content_id',
        'placement_key',
    ];

    public function sponsoredContent(): BelongsTo
    {
        return $this->belongsTo(SponsoredContent::class);
    }
}
