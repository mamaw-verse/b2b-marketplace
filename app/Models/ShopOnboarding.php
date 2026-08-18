<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
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
 * @property string|null $product_categories
 * @property string|null $fda_lto_number
 * @property int|null $average_production_lead_time
 * @property string $export_readiness_status
 * @property array<int, string> $value_chain_positions
 * @property array<int, string> $capabilities
 * @property string $status
 * @property int|null $shop_id
 * @property string|null $admin_notes
 * @property Carbon|null $submitted_at
 * @property Carbon|null $reviewed_at
 * @property int|null $reviewed_by_id
 * @property mixed $geo_location
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ShopOnboarding extends Model
{
    protected $fillable = [
        'user_id',
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
        'product_categories',
        'fda_lto_number',
        'average_production_lead_time',
        'export_readiness_status',
        'value_chain_positions',
        'capabilities',
        'status',
        'shop_id',
        'admin_notes',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_id',
        'geo_location',
    ];

    /**
     * The user who created this onboarding.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who reviewed this onboarding.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * The shop created upon approval.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Halal certificates attached to this onboarding.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(ShopOnboardingCertificate::class, 'onboarding_id');
    }

    /**
     * Documents attached to this onboarding.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'shop_onboarding_documents', 'onboarding_id', 'media_id');
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
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'average_production_lead_time' => 'integer',
        ];
    }
}
