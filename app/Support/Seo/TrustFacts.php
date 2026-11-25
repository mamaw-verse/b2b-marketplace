<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Jobs\ScanCertificateExpiryJob;
use App\Models\PayoutSetting;
use App\Models\SubscriptionPlan;

/**
 * The single source of machine-readable trust claims (T5.3).
 *
 * Anything that states what the platform does for buyers and suppliers in a form
 * crawlers and answer engines read (llms.txt, FAQ, structured data, about copy)
 * takes it from here, so a capability that is switched off in
 * `config/marketplace.php` is never advertised. A fact is only returned when the
 * behaviour behind it is enabled; claims are limited to what the code does.
 *
 * Fees (approved): a 10% commission on completed sales and an annual membership, with the
 * first six months waived in the Philippines. Nothing here hardcodes either: the rate is
 * `PayoutSetting::getCommissionFeeRate()` (the value payouts use) and the membership is the
 * active yearly `SubscriptionPlan` (price, currency and `trial_days`), so the copy follows
 * the real configuration. No fee is stated while its feature is off.
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
        'commission' => 'marketplace.features.orders_enabled',
        'membership' => 'marketplace.features.subscriptions_enabled',
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
            if ($flag !== null && ! (bool) config($flag, false)) {
                continue;
            }

            // The membership claim needs a real, active yearly plan behind it.
            if ($key === 'membership' && $this->membership() === null) {
                continue;
            }

            $facts[$key] = $this->text($key);
        }

        return $facts;
    }

    /**
     * The commission rate as a percentage string without trailing zeros, e.g. "10" or "2.5".
     */
    public function commissionPercent(): string
    {
        return rtrim(rtrim(number_format(PayoutSetting::getCommissionFeeRate() * 100, 2, '.', ''), '0'), '.');
    }

    /**
     * The annual membership shown to suppliers, or null when subscriptions are off or no
     * active yearly plan exists. `trial_months` is the waived period (180 days = 6 months),
     * null when the plan has no free period.
     *
     * @return array{amount: string, trial_months: int|null}|null
     */
    public function membership(): ?array
    {
        if (! (bool) config('marketplace.features.subscriptions_enabled', false)) {
            return null;
        }

        $plan = SubscriptionPlan::query()
            ->where('is_active', true)
            ->where('billing_period', 'yearly')
            ->orderBy('price')
            ->first();

        if ($plan === null || (float) $plan->price <= 0) {
            return null;
        }

        $price = (float) $plan->price;
        $trialDays = (int) $plan->trial_days;

        return [
            'amount' => $plan->currency.' '.number_format($price, fmod($price, 1.0) === 0.0 ? 0 : 2),
            'trial_months' => $trialDays > 0 ? max(1, (int) round($trialDays / 30)) : null,
        ];
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
            'commission' => __('Suppliers pay a :rate% commission on completed sales.', ['rate' => $this->commissionPercent()]),
            'membership' => $this->membershipSentence(),
        };
    }

    private function membershipSentence(): string
    {
        $membership = $this->membership() ?? ['amount' => '', 'trial_months' => null];

        if ($membership['trial_months'] === null) {
            return __('Suppliers pay an annual membership of :amount per year.', ['amount' => $membership['amount']]);
        }

        return __('Suppliers pay an annual membership of :amount per year, and the first :months months are free.', [
            'amount' => $membership['amount'],
            'months' => $membership['trial_months'],
        ]);
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
