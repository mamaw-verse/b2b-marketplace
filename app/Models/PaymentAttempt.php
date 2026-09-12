<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PaymentAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $order_payment_term_id
 * @property int|null $subscription_id
 * @property string $payment_method 'dlt' | 'manual'
 * @property string $status
 * @property string|null $dlt_merchant_transaction_id
 * @property string|null $dlt_quick_link_token
 * @property string|null $dlt_payment_url
 * @property string|null $dlt_payment_status
 * @property string|null $dlt_amount
 * @property array|null $gateway_response
 * @property string|null $manual_reference_number
 * @property string|null $manual_receipt_path
 * @property int $sync_attempts
 * @property Carbon|null $next_sync_at
 * @property Carbon|null $succeeded_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class PaymentAttempt extends Model
{
    /** @use HasFactory<PaymentAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'order_payment_term_id',
        'subscription_id',
        'payment_method',
        'status',
        'dlt_merchant_transaction_id',
        'dlt_quick_link_token',
        'dlt_payment_url',
        'dlt_payment_status',
        'dlt_amount',
        'gateway_response',
        'manual_reference_number',
        'manual_receipt_path',
        'succeeded_at',
        'failed_at',
        'sync_attempts',
        'next_sync_at',
    ];

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(OrderPaymentTerm::class, 'order_payment_term_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isManual(): bool
    {
        return $this->payment_method === 'manual';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway_response' => 'array',
            'succeeded_at' => 'datetime',
            'failed_at' => 'datetime',
            'next_sync_at' => 'datetime',
        ];
    }
}
