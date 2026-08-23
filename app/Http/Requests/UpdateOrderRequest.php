<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrderRequest extends FormRequest
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
            // Customer snapshot fields (all optional — partial update)
            'customer_business_name' => ['nullable', 'string', 'max:255'],
            'customer_contact_name' => ['nullable', 'string', 'max:255'],
            'customer_contact_phone' => ['nullable', 'string', 'max:30'],
            'customer_contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'billing_address_1' => ['nullable', 'string', 'max:255'],
            'billing_address_2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_province' => ['nullable', 'string', 'max:120'],
            'billing_zip_code' => ['nullable', 'string', 'max:20'],
            'billing_country_code' => ['nullable', 'string', 'size:2'],
            'shipping_address_1' => ['nullable', 'string', 'max:255'],
            'shipping_address_2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_province' => ['nullable', 'string', 'max:120'],
            'shipping_zip_code' => ['nullable', 'string', 'max:20'],
            'shipping_country_code' => ['nullable', 'string', 'size:2'],

            // Items (full replace array when present)
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:500'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'],
            'items.*.unit' => ['required_with:items', 'string', 'max:30'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'gte:0'],

            // Fees (full replace array when present)
            'fees' => ['nullable', 'array'],
            'fees.*.label' => ['required_with:fees', 'string', 'max:255'],
            'fees.*.amount' => ['required_with:fees', 'numeric', 'gte:0'],
        ];
    }
}
