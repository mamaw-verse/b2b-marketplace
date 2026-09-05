<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sponsored_content_tier_id
 * @property string $title
 * @property int $media_id
 * @property string $destination_url
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property bool $is_active
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class SponsoredContent extends Model
{
    protected $table = 'sponsored_content';

    protected $fillable = [
        'sponsored_content_tier_id',
        'title',
        'media_id',
        'destination_url',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(SponsoredContentTier::class, 'sponsored_content_tier_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(SponsoredContentPlacement::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SponsoredContentEvent::class);
    }

    /**
     * Scope to content that is currently active and within its schedule window.
     *
     * @param  Builder<SponsoredContent>  $query
     * @return Builder<SponsoredContent>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    /**
     * Scope to content targeting a specific placement key.
     *
     * @param  Builder<SponsoredContent>  $query
     * @return Builder<SponsoredContent>
     */
    public function scopeForPlacement(Builder $query, string $placementKey): Builder
    {
        return $query->whereHas('placements', fn (Builder $q) => $q->where('placement_key', $placementKey));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
