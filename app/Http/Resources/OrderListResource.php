<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderListResource extends JsonResource
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
            'currency' => $this->currency,
            'order_total' => $this->order_total,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
