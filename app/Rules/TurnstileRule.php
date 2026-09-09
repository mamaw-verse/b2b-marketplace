<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

final class TurnstileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app()->environment('testing')) {
            if ($value !== 'valid-token') {
                $fail(__('The CAPTCHA validation failed. Please try again.'));
            }

            return;
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (! $response->successful() || ! $response->json('success')) {
            $fail(__('The CAPTCHA validation failed. Please try again.'));
        }
    }
}
