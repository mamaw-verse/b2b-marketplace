<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'user_id',
        'status',
        'geo_location',
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
     * Halal certificates for this shop.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(ShopCertificate::class);
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
     * Scope a query to only include active shops.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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
