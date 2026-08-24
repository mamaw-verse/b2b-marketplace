<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class DisbursementStatus
{
    /**
     * @param  string  $status  One of: 'pending', 'succeeded', 'failed'
     * @param  array<string, mixed>|null  $rawResponse
     */
    public function __construct(
        public string $status,
        public ?array $rawResponse = null,
    ) {}
}
