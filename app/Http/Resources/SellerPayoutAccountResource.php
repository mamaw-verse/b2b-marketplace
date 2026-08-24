<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellerPayoutAccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'account_holder_name' => $this->account_holder_name,
            'account_number' => $this->account_number,
            'branch' => $this->branch,
            'verification_status' => $this->verification_status,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'shop' => $this->whenLoaded('shop', fn () => [
                'id' => $this->shop->id,
                'storefront_name' => $this->shop->storefront_name,
                'registered_business_name' => $this->shop->registered_business_name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
