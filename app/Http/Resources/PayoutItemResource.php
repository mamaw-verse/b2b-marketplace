<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PayoutItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payout_id' => $this->payout_id,
            'order_id' => $this->order_id,
            'ledger_entry_id' => $this->ledger_entry_id,
            'escrowed_amount' => $this->escrowed_amount,
            'transaction_fee' => $this->transaction_fee,
            'commission_fee' => $this->commission_fee,
            'net_amount' => $this->net_amount,
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'order_total' => $this->order->order_total,
                'status' => $this->order->status,
                'completed_at' => $this->order->completed_at?->toIso8601String(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
