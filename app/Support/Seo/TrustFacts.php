<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Jobs\ScanCertificateExpiryJob;

/**
 * The single source of machine-readable trust claims (T5.3).
 *
 * Anything that states what the platform does for buyers and suppliers in a form
 * crawlers and answer engines read (llms.txt, FAQ, structured data, about copy)
 * takes it from here, so a capability that is switched off in
 * `config/marketplace.php` is never advertised. A fact is only returned when the
 * behaviour behind it is enabled; claims are limited to what the code does.
 *
 * Deliberately absent: the commission rate and "no monthly fee". The storefront copy
 * hardcodes 10% while `marketplace.commission_rate` drives the real calculation, and
 * subscriptions can be switched on, so neither can be stated safely until reconciled.
 */
final class TrustFacts
{
    /**
     * Fact key => feature flag (config path) that must be on, or null when always true.
     *
     * @var array<string, string|null>
     */
    private const array GATES = [
        'onboarding_approval' => null,
        'certificate_tracking' => null,
        'document_scanning' => 'services.clamav.enabled',
        'quotations' => 'marketplace.features.quotations_enabled',
        'chat' => 'marketplace.features.chat_enabled',
        'orders' => 'marketplace.features.orders_enabled',
        'escrow' => 'marketplace.features.escrow_payments_enabled',
        'disputes' => 'marketplace.features.disputes_enabled',
        'reviews' => 'marketplace.features.reviews_enabled',
    ];

    /**
     * Enabled facts as key => sentence, in display order.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $facts = [];

        foreach (self::GATES as $key => $flag) {
            if ($flag === null || (bool) config($flag, false)) {
                $facts[$key] = $this->text($key);
            }
        }

        return $facts;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @return list<string>
     */
    public function sentences(): array
    {
        return array_values($this->all());
    }

    private function text(string $key): string
    {
        return match ($key) {
            'onboarding_approval' => __('Suppliers are reviewed and approved by an administrator before they appear in the directory.'),
            'certificate_tracking' => __('Halal certificates uploaded by suppliers are tracked as active, expiring soon or expired, and suppliers are alerted :days days before expiry and when a certificate expires.', ['days' => $this->alertDays()]),
            'document_scanning' => __('Uploaded documents are scanned for malware.'),
            'quotations' => __('Suppliers can respond to buyer requests with formal quotations.'),
            'chat' => __('Buyers and suppliers can message each other on the platform.'),
            'orders' => __('Buyers can place and track orders with suppliers on the platform.'),
            'escrow' => __('Order payments are protected by escrow.'),
            'disputes' => __('Buyers can raise a dispute on an order.'),
            'reviews' => __('Buyers can leave ratings and reviews on products.'),
        };
    }

    /**
     * The pre-expiry alert thresholds, read from the scheduled job so the claim cannot drift
     * from behaviour, e.g. "60, 30 and 7".
     */
    private function alertDays(): string
    {
        $days = array_values(array_filter(ScanCertificateExpiryJob::THRESHOLDS, static fn (int $d): bool => $d > 0));
        $last = array_pop($days);

        return $days === [] ? (string) $last : implode(', ', $days).' and '.$last;
    }
}
