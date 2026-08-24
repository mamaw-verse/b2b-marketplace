<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LedgerEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
            'order' => $this->when($this->relationLoaded('order'), fn () => [
                'id' => $this->order->id,
                'shop_id' => $this->order->shop_id,
                'buyer_user_id' => $this->order->buyer_user_id,
                'order_total' => $this->order->order_total,
                'status' => $this->order->status,
                'shop_name' => $this->order->shop?->storefront_name,
            ]),
        ];
    }
}
