<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PayoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'status' => $this->status,
            'currency' => $this->currency,
            'gross_amount' => $this->gross_amount,
            'total_transaction_fees' => $this->total_transaction_fees,
            'total_commission_fees' => $this->total_commission_fees,
            'net_amount' => $this->net_amount,
            'orders_count' => $this->orders_count,
            'failure_reason' => $this->failure_reason,
            'released_by' => $this->whenLoaded('releasedBy', fn () => [
                'id' => $this->releasedBy->id,
                'first_name' => $this->releasedBy->first_name,
                'last_name' => $this->releasedBy->last_name,
            ]),
            'shop' => $this->whenLoaded('shop', fn () => [
                'id' => $this->shop->id,
                'storefront_name' => $this->shop->storefront_name,
                'registered_business_name' => $this->shop->registered_business_name,
            ]),
            'items' => PayoutItemResource::collection($this->whenLoaded('items')),
            'disbursements' => $this->whenLoaded('disbursements'),
            'released_at' => $this->released_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
