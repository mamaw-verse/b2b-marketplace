<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SlugRedirectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $sluggable_type
 * @property int $sluggable_id
 * @property string $slug
 */
final class SlugRedirect extends Model
{
    /** @use HasFactory<SlugRedirectFactory> */
    use HasFactory;

    protected $fillable = [
        'sluggable_type',
        'sluggable_id',
        'slug',
    ];

    /** @return MorphTo<Model, $this> */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }
}
