<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ShopCertificate;
use App\Notifications\CertificateExpiryNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

final class ScanCertificateExpiryJob implements ShouldQueue
{
    use Queueable;

    /**
     * Thresholds in descending order (days before expiry).
     * 0 means the certificate has expired (expiry_date is today or past).
     *
     * @var array<int, int>
     */
    private const array THRESHOLDS = [60, 30, 7, 0];

    /**
     * Scan all active/expiring_soon certificates and send alerts at threshold crossings.
     */
    public function handle(): void
    {
        $today = Carbon::today();

        ShopCertificate::query()
            ->whereIn('status', ['active', 'expiring_soon'])
            ->where('expiry_date', '<=', $today->copy()->addDays(self::THRESHOLDS[0]))
            ->with(['shop.user'])
            ->chunk(200, function ($certificates) use ($today) {
                foreach ($certificates as $certificate) {
                    $this->processCertificate($certificate, $today);
                }
            });
    }

    private function processCertificate(ShopCertificate $certificate, Carbon $today): void
    {
        $daysUntilExpiry = (int) $today->diffInDays($certificate->expiry_date, false);
        $currentThreshold = $this->determineThreshold($daysUntilExpiry);

        if ($currentThreshold === null) {
            return;
        }

        // Idempotent: skip if we already alerted at this threshold or a lower one
        if (
            $certificate->last_alert_threshold_days !== null
            && $certificate->last_alert_threshold_days <= $currentThreshold
        ) {
            return;
        }

        // Transition status
        $this->transitionStatus($certificate, $daysUntilExpiry);

        // Send notification
        $user = $certificate->shop->user;
        if ($user) {
            $user->notify(new CertificateExpiryNotification($certificate, $daysUntilExpiry));
        }

        // Record the threshold to prevent duplicates
        $certificate->update(['last_alert_threshold_days' => $currentThreshold]);
    }

    /**
     * Determine which threshold has been crossed.
     * Returns the threshold value, or null if no threshold is crossed yet.
     */
    private function determineThreshold(int $daysUntilExpiry): ?int
    {
        // Find the smallest threshold that is >= daysUntilExpiry
        // Thresholds: 60, 30, 7, 0
        // If days=55, crossed threshold 60
        // If days=25, crossed threshold 30
        // If days=5, crossed threshold 7
        // If days<=0, crossed threshold 0
        foreach (array_reverse(self::THRESHOLDS) as $threshold) {
            if ($daysUntilExpiry <= $threshold) {
                return $threshold;
            }
        }

        return null;
    }

    private function transitionStatus(ShopCertificate $certificate, int $daysUntilExpiry): void
    {
        if ($daysUntilExpiry <= 0 && $certificate->status !== 'expired') {
            $certificate->update(['status' => 'expired']);
        } elseif ($daysUntilExpiry > 0 && $daysUntilExpiry <= 60 && $certificate->status === 'active') {
            $certificate->update(['status' => 'expiring_soon']);
        }
    }
}
