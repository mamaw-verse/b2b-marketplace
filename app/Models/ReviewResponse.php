<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReviewResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $review_id
 * @property int $responder_user_id
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class ReviewResponse extends Model
{
    /** @use HasFactory<ReviewResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'review_id',
        'responder_user_id',
        'body',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_user_id');
    }
}
