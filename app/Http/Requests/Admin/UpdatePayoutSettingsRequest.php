<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePayoutSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dispute_hold_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:365'],
            'minimum_payout_threshold' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:999999999999'],
            'transaction_fee_amount' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999999999'],
            'commission_fee_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dispute_hold_days.min' => 'Dispute hold days must be at least 1.',
            'dispute_hold_days.max' => 'Dispute hold days cannot exceed 365.',
            'minimum_payout_threshold.min' => 'Minimum payout threshold must be positive.',
            'commission_fee_rate.max' => 'Commission fee rate cannot exceed 1 (100%).',
        ];
    }
}
