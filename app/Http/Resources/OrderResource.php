<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'quotation_id' => $this->quotation_id,
            'buyer' => [
                'id' => $this->buyer_user_id,
                'name' => $this->buyer?->first_name.' '.$this->buyer?->last_name,
                'business_name' => $this->customer_business_name,
            ],
            'shop' => [
                'id' => $this->shop_id,
                'name' => $this->shop?->storefront_name,
            ],
            'customer_business_name' => $this->customer_business_name,
            'customer_contact_name' => $this->customer_contact_name,
            'customer_contact_phone' => $this->customer_contact_phone,
            'customer_contact_email' => $this->customer_contact_email,
            'billing_address_1' => $this->billing_address_1,
            'billing_address_2' => $this->billing_address_2,
            'billing_city' => $this->billing_city,
            'billing_province' => $this->billing_province,
            'billing_zip_code' => $this->billing_zip_code,
            'billing_country_code' => $this->billing_country_code,
            'shipping_address_1' => $this->shipping_address_1,
            'shipping_address_2' => $this->shipping_address_2,
            'shipping_city' => $this->shipping_city,
            'shipping_province' => $this->shipping_province,
            'shipping_zip_code' => $this->shipping_zip_code,
            'shipping_country_code' => $this->shipping_country_code,
            'currency' => $this->currency,
            'items_total' => $this->items_total,
            'fees_total' => $this->fees_total,
            'order_total' => $this->order_total,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
                'sort_order' => $item->sort_order,
            ])),
            'fees' => $this->whenLoaded('fees', fn () => $this->fees->map(fn ($fee) => [
                'id' => $fee->id,
                'label' => $fee->label,
                'amount' => $fee->amount,
                'sort_order' => $fee->sort_order,
            ])),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
