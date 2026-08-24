<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class DisbursementResult
{
    /**
     * @param  array<string, mixed>|null  $rawResponse
     */
    public function __construct(
        public bool $success,
        public ?string $dltReferenceId = null,
        public ?string $dltTransactionId = null,
        public ?string $errorMessage = null,
        public ?array $rawResponse = null,
    ) {}
}
