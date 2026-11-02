<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\TurnstileVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class TurnstileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('The CAPTCHA validation failed. Please try again.'));

            return;
        }

        /** @var TurnstileVerifier $verifier */
        $verifier = app(TurnstileVerifier::class);

        $result = $verifier->verify(
            token: $value,
            remoteIp: request()->ip(),
        );

        if (! $result->success) {
            $fail(__('The CAPTCHA validation failed. Please try again.'));
        }
    }
}
