<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->resource_type,
            'amount' => $this->amount,
            'status' => $this->resource_type === 'payment' ? 'paid' : 'refunded',
            'order_id' => $this->order_id,
            'note' => $this->note ?? null,
            'date' => $this->date?->toIso8601String(),
        ];
    }
}
