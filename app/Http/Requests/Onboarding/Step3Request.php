<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Step 3 validation is lightweight since certificates are managed
 * via dedicated POST/DELETE endpoints. This step just confirms
 * the user has visited and acknowledged the certification section.
 */
final class Step3Request extends FormRequest
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
