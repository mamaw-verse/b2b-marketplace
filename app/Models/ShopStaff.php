<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ShopStaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property int|null $user_id
 * @property string $email
 * @property string|null $token_hash
 * @property Carbon|null $expires_at
 * @property int $invited_by_id
 * @property Carbon|null $accepted_at
 * @property Carbon|null $removed_at
 * @property int|null $removed_by_id
 * @property string $status
 */
final class ShopStaff extends Model
{
    /** @use HasFactory<ShopStaffFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id', 'user_id', 'email', 'token_hash', 'expires_at', 'invited_by_id',
        'accepted_at', 'removed_at', 'removed_by_id', 'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'removed_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }
}
