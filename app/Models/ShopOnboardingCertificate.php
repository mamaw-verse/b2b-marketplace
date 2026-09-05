<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $onboarding_id
 * @property string|null $certification_body
 * @property string|null $certification_body_other
 * @property string|null $registration_number
 * @property Carbon|null $expiry_date
 * @property int|null $media_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ShopOnboardingCertificate extends Model
{
    protected $fillable = [
        'onboarding_id',
        'certification_body',
        'certification_body_other',
        'registration_number',
        'expiry_date',
        'media_id',
    ];

    /**
     * The onboarding this certificate belongs to.
     */
    public function onboarding(): BelongsTo
    {
        return $this->belongsTo(ShopOnboarding::class, 'onboarding_id');
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
        ];
    }
}
