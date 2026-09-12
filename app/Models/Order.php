<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $order_number
 * @property int $buyer_user_id
 * @property int $shop_id
 * @property int|null $quotation_id
 * @property string|null $customer_business_name
 * @property string|null $customer_contact_name
 * @property string|null $customer_contact_phone
 * @property string|null $customer_contact_email
 * @property string|null $billing_address_1
 * @property string|null $billing_address_2
 * @property string|null $billing_city
 * @property string|null $billing_province
 * @property string|null $billing_zip_code
 * @property string $billing_country_code
 * @property string|null $shipping_address_1
 * @property string|null $shipping_address_2
 * @property string|null $shipping_city
 * @property string|null $shipping_province
 * @property string|null $shipping_zip_code
 * @property string $shipping_country_code
 * @property string $currency
 * @property string|null $items_total
 * @property string|null $fees_total
 * @property string|null $order_total
 * @property string $status
 * @property Carbon|null $submitted_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'buyer_user_id',
        'shop_id',
        'quotation_id',
        'customer_business_name',
        'customer_contact_name',
        'customer_contact_phone',
        'customer_contact_email',
        'billing_address_1',
        'billing_address_2',
        'billing_city',
        'billing_province',
        'billing_zip_code',
        'billing_country_code',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_city',
        'shipping_province',
        'shipping_zip_code',
        'shipping_country_code',
        'currency',
        'items_total',
        'fees_total',
        'order_total',
        'status',
        'submitted_at',
        'paid_at',
        'completed_at',
        'cancelled_at',
    ];

    // ──────────────────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────────────────

    /**
     * Human-readable order reference number.
     * Format: ORD-YYYYMM-NNNNN  (e.g. ORD-202609-00042)
     * Derived from the record's primary key and creation month so it is always
     * unique, sortable, and clearly dated without requiring a separate column.
     */
    public function getOrderNumberAttribute(): string
    {
        $month = ($this->created_at ?? now())->format('Ym');

        return 'ORD-'.$month.'-'.mb_str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(OrderFee::class);
    }

    public function paymentTerms(): HasMany
    {
        return $this->hasMany(OrderPaymentTerm::class);
    }

    public function fulfilments(): HasMany
    {
        return $this->hasMany(Fulfilment::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function payoutItem(): HasOne
    {
        return $this->hasOne(PayoutItem::class);
    }

    // ──────────────────────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────────────────────

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeForBuyer(Builder $query, int $userId): void
    {
        $query->where('buyer_user_id', $userId);
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeForShop(Builder $query, int $shopId): void
    {
        $query->where('shop_id', $shopId);
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeInStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
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
            'items_total' => 'decimal:2',
            'fees_total' => 'decimal:2',
            'order_total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
