<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class Step2Request extends FormRequest
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
            'product_categories' => ['required', 'string', 'max:500'],
            'fda_lto_number' => ['nullable', 'string', 'max:100'],
            'average_production_lead_time' => ['nullable', 'integer', 'min:1', 'max:365'],
            'export_readiness_status' => ['required', Rule::in(['never_exported', 'exporting_regionally', 'exporting_globally'])],
        ];
    }
}
