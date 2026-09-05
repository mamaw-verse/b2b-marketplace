<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Google\Analytics\Data\V1beta\AnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;

final class GoogleAnalyticsService
{
    private ?AnalyticsDataClient $client;

    private ?string $propertyId;

    public function __construct(?AnalyticsDataClient $client = null, ?string $propertyId = null)
    {
        $this->propertyId = $propertyId ?? config('services.google_analytics.property_id');
        $credentials = config('services.google_analytics.credentials');

        if ($client !== null) {
            $this->client = $client;
        } elseif ($this->propertyId && $credentials && file_exists($credentials)) {
            $this->client = AnalyticsDataClient::build([
                'credentials' => $credentials,
            ]);
        } else {
            $this->client = null;
        }
    }

    public function isConfigured(): bool
    {
        return $this->client !== null && $this->propertyId !== null;
    }

    /**
     * @return array{active_users: int, sessions: int, page_views: int, bounce_rate: float, avg_session_duration: float}|null
     */
    public function getOverview(string $from, string $to): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $request = RunReportRequest::create()
                ->setPropertyName("properties/{$this->propertyId}")
                ->setDateRanges([
                    DateRange::create()
                        ->setStartDate($from)
                        ->setEndDate($to),
                ])
                ->setMetrics([
                    Metric::create()->setName('activeUsers'),
                    Metric::create()->setName('sessions'),
                    Metric::create()->setName('screenPageViews'),
                    Metric::create()->setName('bounceRate'),
                    Metric::create()->setName('averageSessionDuration'),
                ]);

            $response = $this->client->runReport($request);

            $row = $response->getRows()->offsetGet(0) ?? null;

            if ($row === null) {
                return $this->emptyMetrics();
            }

            $metricValues = $row->getMetricValues();

            return [
                'active_users' => (int) ($metricValues->offsetGet(0)?->getValue() ?? 0),
                'sessions' => (int) ($metricValues->offsetGet(1)?->getValue() ?? 0),
                'page_views' => (int) ($metricValues->offsetGet(2)?->getValue() ?? 0),
                'bounce_rate' => round((float) ($metricValues->offsetGet(3)?->getValue() ?? 0), 1),
                'avg_session_duration' => round((float) ($metricValues->offsetGet(4)?->getValue() ?? 0), 0),
            ];
        } catch (Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * @return array{active_users: int, sessions: int, page_views: int, bounce_rate: float, avg_session_duration: float}
     */
    private function emptyMetrics(): array
    {
        return [
            'active_users' => 0,
            'sessions' => 0,
            'page_views' => 0,
            'bounce_rate' => 0.0,
            'avg_session_duration' => 0.0,
        ];
    }
}
