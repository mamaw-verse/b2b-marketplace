<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\IndexShopJob;
use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $registered_business_name
 * @property string $storefront_name
 * @property string $business_structure
 * @property string $dti_sec_number
 * @property string $tax_identification_number
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address_1
 * @property string|null $address_2
 * @property string|null $city
 * @property string|null $province
 * @property string|null $zip_code
 * @property string $country_code
 * @property string|null $fda_lto_number
 * @property int|null $average_production_lead_time
 * @property string $export_readiness_status
 * @property array<int, string> $value_chain_positions
 * @property array<int, string> $capabilities
 * @property int $user_id
 * @property string $status
 * @property mixed $geo_location
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read bool $is_halal_certified
 */
final class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'registered_business_name',
        'storefront_name',
        'business_structure',
        'dti_sec_number',
        'tax_identification_number',
        'phone',
        'email',
        'address_1',
        'address_2',
        'city',
        'province',
        'zip_code',
        'country_code',
        'fda_lto_number',
        'average_production_lead_time',
        'export_readiness_status',
        'value_chain_positions',
        'capabilities',
        'company_description',
        'products_description',
        'user_id',
        'status',
        'geo_location',
        'average_rating',
        'total_reviews',
    ];

    public function searchableAs(): string
    {
        return 'shops';
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'storefront_name' => $this->storefront_name,
            'registered_business_name' => $this->registered_business_name,
            'city' => $this->city ?? '',
            'province' => $this->province ?? '',
            'country_code' => $this->country_code,
            'value_chain_positions' => $this->value_chain_positions ?? [],
            'capabilities' => $this->capabilities ?? [],
            'categories' => $this->categories->pluck('name')->toArray(),
            'certification_bodies' => $this->certificates->pluck('certification_body')->unique()->values()->toArray(),
            'export_readiness_status' => $this->export_readiness_status,
            'average_rating' => (float) ($this->average_rating ?? 0),
            'total_reviews' => (int) ($this->total_reviews ?? 0),
            'created_at' => $this->created_at->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'active' && $this->deleted_at === null;
    }

    /**
     * The user who owns this shop (1:1 in Phase 1).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The onboarding record that produced this shop.
     */
    public function onboarding(): HasOne
    {
        return $this->hasOne(ShopOnboarding::class);
    }

    /**
     * Whether this shop has at least one valid (non-expired) halal certificate.
     * Includes certificates with status 'active' or 'expiring_soon'.
     */
    public function getIsHalalCertifiedAttribute(): bool
    {
        return $this->certificates->contains(
            fn (ShopCertificate $cert) => in_array($cert->status, ['active', 'expiring_soon'], true)
        );
    }

    /**
     * Halal certificates for this shop.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(ShopCertificate::class);
    }

    /**
     * Certification applications submitted by this shop.
     */
    public function certificationApplications(): HasMany
    {
        return $this->hasMany(CertificationApplication::class);
    }

    /**
     * Primary product categories assigned to this shop.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'shop_categories');
    }

    /**
     * Documents attached to this shop.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'shop_documents')
            ->withPivot('is_verified');
    }

    /**
     * Products listed by this shop.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Orders received by this shop (seller side).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * RFQ recipients targeting this shop.
     */
    public function rfqRecipients(): HasMany
    {
        return $this->hasMany(RfqRecipient::class);
    }

    /**
     * The seller's payout/bank account for this shop.
     */
    public function payoutAccount(): HasOne
    {
        return $this->hasOne(SellerPayoutAccount::class);
    }

    /**
     * Payout batches for this shop.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Subscriptions for this shop.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Most recent subscription record for this shop.
     */
    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Determine whether the shop has an active or trialing subscription.
     */
    public function hasActiveOrTrialingSubscription(): bool
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->exists();
    }

    /**
     * Reviews for this shop.
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Scope a query to only include active shops.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
        IndexShopJob::dispatch($this)->afterCommit();
    }

    public function reactivate(): void
    {
        $this->update(['status' => 'active']);
        IndexShopJob::dispatch($this)->afterCommit();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_chain_positions' => 'array',
            'capabilities' => 'array',
            'average_production_lead_time' => 'integer',
        ];
    }
}
