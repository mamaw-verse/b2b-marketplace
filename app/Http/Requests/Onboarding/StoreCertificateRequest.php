<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'certification_body' => ['required', Rule::in(['IDCP', 'HDIP', 'PRIME_CERTIFICATION', 'OTHER_NCMF'])],
            'certification_body_other' => ['nullable', 'required_if:certification_body,OTHER_NCMF', 'string', 'max:150'],
            'registration_number' => ['required', 'string', 'max:100'],
            'expiry_date' => ['required', 'date', 'after:today'],
            'media_id' => ['required', 'integer', 'exists:media,id'],
        ];
    }
}
