<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\ShopCertificate;
use App\Support\Schema\PlainText;

/**
 * A halal certificate as it may be published in structured data: the body and
 * its status only. Registration numbers, expiry dates and the scanned document
 * are never exposed (decision D3).
 */
final readonly class PublicCertification
{
    private const BODY_LABELS = [
        'IDCP' => 'IDCP',
        'HDIP' => 'HDIP',
        'PRIME_CERTIFICATION' => 'Prime Certification',
    ];

    private const GENERIC_NAME = 'Halal certification';

    public function __construct(
        public string $name,
        public ?string $issuer,
    ) {}

    /**
     * Only certificates that are active, admin-verified and not past their
     * expiry date qualify; anything else yields null and is never published.
     */
    public static function fromCertificate(ShopCertificate $certificate): ?self
    {
        if ($certificate->status !== 'active' || ! $certificate->is_verified) {
            return null;
        }

        if ($certificate->expiry_date !== null && $certificate->expiry_date->copy()->endOfDay()->isPast()) {
            return null;
        }

        $body = (string) $certificate->certification_body;

        if ($body === 'OTHER_NCMF') {
            $issuer = PlainText::from($certificate->certification_body_other);

            return new self($issuer ?? self::GENERIC_NAME, $issuer);
        }

        $label = self::BODY_LABELS[$body] ?? null;

        return new self($label ?? self::GENERIC_NAME, $label);
    }
}
