<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Step 5 validation is lightweight since documents are uploaded
 * via the dedicated POST /onboarding/documents endpoint.
 * This step just confirms the user has visited the document section.
 */
final class Step5Request extends FormRequest
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
        return [];
    }
}
