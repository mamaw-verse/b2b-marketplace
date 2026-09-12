<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BuyerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $business_name
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
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class BuyerProfile extends Model
{
    /** @use HasFactory<BuyerProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
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
        'contact_phone',
        'contact_email',
    ];

    /**
     * The user who owns this buyer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Build the snapshot array used for RFQs and quotations.
     *
     * @return array{business_name: string, billing_address: array<string, string|null>, shipping_address: array<string, string|null>}
     */
    public function toSnapshot(): array
    {
        return [
            'business_name' => $this->business_name,
            'billing_address' => [
                'address_1' => $this->billing_address_1,
                'address_2' => $this->billing_address_2,
                'city' => $this->billing_city,
                'province' => $this->billing_province,
                'zip_code' => $this->billing_zip_code,
                'country_code' => $this->billing_country_code,
            ],
            'shipping_address' => [
                'address_1' => $this->shipping_address_1,
                'address_2' => $this->shipping_address_2,
                'city' => $this->shipping_city,
                'province' => $this->shipping_province,
                'zip_code' => $this->shipping_zip_code,
                'country_code' => $this->shipping_country_code,
            ],
        ];
    }
}
