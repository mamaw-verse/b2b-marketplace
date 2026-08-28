<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via Gate in controller
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
        /** @var ProductVariant $variant */
        $variant = $this->route('variant');

        return [
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('product_variants')->where('product_id', $product->id)->ignore($variant->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'attributes' => ['nullable', 'array'],
            'price' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'minimum_order_quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'quantity_breaks' => ['nullable', 'array'],
            'quantity_breaks.*.min_qty' => ['required_with:quantity_breaks', 'integer', 'min:1'],
            'quantity_breaks.*.price' => ['required_with:quantity_breaks', 'numeric', 'gt:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'available_quantity' => ['nullable', 'integer', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'pack_size' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['archived', 'published'])],
        ];
    }
}
