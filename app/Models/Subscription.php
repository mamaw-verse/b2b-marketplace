<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property int $subscription_plan_id
 * @property string $status
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $current_period_start
 * @property Carbon|null $current_period_end
 * @property int|null $last_renewal_alert_threshold_days
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'subscription_plan_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'last_renewal_alert_threshold_days',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function isActiveOrTrialing(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'last_renewal_alert_threshold_days' => 'integer',
        ];
    }
}
