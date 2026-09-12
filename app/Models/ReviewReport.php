<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReviewReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $review_id
 * @property int $reporter_user_id
 * @property string $reason
 * @property string $status
 * @property int|null $resolved_by_id
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ReviewReport extends Model
{
    /** @use HasFactory<ReviewReportFactory> */
    use HasFactory;

    protected $fillable = [
        'review_id',
        'reporter_user_id',
        'reason',
        'status',
        'resolved_by_id',
        'resolved_at',
    ];

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Casts
    // ──────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
            'resolved_at' => 'datetime',
        ];
    }
}
