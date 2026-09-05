<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SponsoredContentEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RecordSponsoredContentEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $sponsoredContentId,
        public readonly string $eventType, // impression|click
    ) {}

    public function handle(): void
    {
        SponsoredContentEvent::create([
            'sponsored_content_id' => $this->sponsoredContentId,
            'event_type' => $this->eventType,
            'occurred_at' => now(),
        ]);
    }
}
