<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Rules\TurnstileRule;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\SendPasswordResetLinkRequest;

final class PasswordResetLinkRequest extends SendPasswordResetLinkRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Fortify::email() => ['required', 'email'],
            'cf-turnstile-response' => ['required', 'string', new TurnstileRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cf-turnstile-response.required' => __('Please complete the security check.'),
        ];
    }
}
