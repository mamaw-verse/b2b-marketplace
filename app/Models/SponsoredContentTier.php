<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $priority_weight
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class SponsoredContentTier extends Model
{
    protected $fillable = [
        'name',
        'priority_weight',
        'is_active',
    ];

    public function sponsoredContent(): HasMany
    {
        return $this->hasMany(SponsoredContent::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority_weight' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
