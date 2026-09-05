<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sponsored_content_id
 * @property string $event_type
 * @property Carbon $occurred_at
 */
final class SponsoredContentEvent extends Model
{
    /** No updated_at — this is an append-only event log. */
    public $timestamps = false;

    protected $fillable = [
        'sponsored_content_id',
        'event_type',
        'occurred_at',
    ];

    public function sponsoredContent(): BelongsTo
    {
        return $this->belongsTo(SponsoredContent::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
