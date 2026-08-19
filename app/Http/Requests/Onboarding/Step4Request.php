<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class Step4Request extends FormRequest
{
    private const array VALUE_CHAIN_POSITIONS = [
        'farm',
        'processing',
        'manufacturing',
        'distribution',
        'retail',
        'food_service',
    ];

    private const array CAPABILITIES = [
        'processing',
        'contract_processing',
        'packaging',
        'contract_manufacturing',
        'oem_odm',
        'private_label',
        'cold_storage',
        'warehousing',
        'distribution',
        'wholesale',
        'importing',
        'exporting',
    ];

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
            'value_chain_positions' => ['required', 'array', 'min:1'],
            'value_chain_positions.*' => ['string', Rule::in(self::VALUE_CHAIN_POSITIONS)],
            'capabilities' => ['required', 'array', 'min:1'],
            'capabilities.*' => ['string', Rule::in(self::CAPABILITIES)],
        ];
    }
}
