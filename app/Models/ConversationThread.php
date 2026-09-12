<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConversationThreadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $conversation_id
 * @property string|null $title
 * @property int|null $rfq_id
 * @property int|null $order_id
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ConversationThread extends Model
{
    /** @use HasFactory<ConversationThreadFactory> */
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'title',
        'rfq_id',
        'order_id',
        'is_default',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    /**
     * Whether this thread is linked to an RFQ.
     */
    public function isRfqLinked(): bool
    {
        return $this->rfq_id !== null;
    }

    /**
     * Whether this thread is linked to an Order.
     */
    public function isOrderLinked(): bool
    {
        return $this->order_id !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
