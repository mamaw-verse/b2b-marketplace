<?php

declare(strict_types=1);

namespace App\Services;

final class TurnstileResult
{
    /**
     * @param  list<string>  $errorCodes
     */
    public function __construct(
        public readonly bool $success,
        public readonly array $errorCodes = [],
        public readonly ?string $hostname = null,
        public readonly ?string $action = null,
    ) {}

    /**
     * @param  list<string>  $errorCodes
     */
    public static function failure(array $errorCodes = []): self
    {
        return new self(success: false, errorCodes: $errorCodes);
    }

    public static function success(?string $hostname = null, ?string $action = null): self
    {
        return new self(success: true, hostname: $hostname, action: $action);
    }
}
