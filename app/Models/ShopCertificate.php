<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ShopCertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $shop_id
 * @property string $certification_body
 * @property string|null $certification_body_other
 * @property string $registration_number
 * @property Carbon $expiry_date
 * @property string $status
 * @property int|null $last_alert_threshold_days
 * @property int $media_id
 * @property bool $is_verified
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ShopCertificate extends Model
{
    /** @use HasFactory<ShopCertificateFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'certification_body',
        'certification_body_other',
        'registration_number',
        'expiry_date',
        'status',
        'last_alert_threshold_days',
        'media_id',
        'is_verified',
    ];

    /**
     * The shop this certificate belongs to.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * The media file (certificate scan).
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'is_verified' => 'boolean',
            'last_alert_threshold_days' => 'integer',
        ];
    }
}
