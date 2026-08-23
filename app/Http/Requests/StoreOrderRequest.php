<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via Gate in controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quotation_id' => ['nullable', 'integer', 'exists:quotations,id'],
            'buyer_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quotation_id.exists' => 'The selected quotation does not exist.',
            'buyer_user_id.exists' => 'The selected buyer does not exist.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('quotation_id') && ! $this->filled('buyer_user_id')) {
                $validator->errors()->add('quotation_id', 'Either quotation_id or buyer_user_id must be provided.');
            }

            if ($this->filled('quotation_id') && $this->filled('buyer_user_id')) {
                $validator->errors()->add('quotation_id', 'Provide either quotation_id or buyer_user_id, not both.');
            }
        });
    }
}
