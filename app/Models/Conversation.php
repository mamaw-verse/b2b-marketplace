<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConversationFactory;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $buyer_user_id
 * @property int $shop_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'buyer_user_id',
        'shop_id',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function threads(): HasMany
    {
        return $this->hasMany(ConversationThread::class);
    }

    /**
     * Scope to conversations the user is a participant in (as buyer or shop owner).
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('buyer_user_id', $user->id)
            ->orWhereHas('shop', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    /**
     * Check if a user is a participant in this conversation.
     */
    public function hasParticipant(User $user): bool
    {
        return $user->id === $this->buyer_user_id
            || $user->id === $this->shop->user_id;
    }

    /**
     * Get the unread message count for a user in this conversation.
     */
    public function unreadCountFor(User $user): int
    {
        $lastReadAt = DB::table('conversation_reads')
            ->where('conversation_id', $this->id)
            ->where('user_id', $user->id)
            ->value('last_read_at');

        $query = Message::whereIn(
            'thread_id',
            $this->threads()->pluck('id')
        )->where('sender_id', '!=', $user->id);

        if ($lastReadAt) {
            $query->where('created_at', '>', $lastReadAt);
        }

        return $query->count();
    }

    /**
     * Mark this conversation as read for a user.
     */
    public function markAsReadFor(User $user): void
    {
        DB::table('conversation_reads')->updateOrInsert(
            ['conversation_id' => $this->id, 'user_id' => $user->id],
            ['last_read_at' => now()],
        );
    }
}
