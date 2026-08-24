<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'reviewer' => [
                'id' => $this->reviewer_user_id,
                'name' => $this->reviewer?->first_name.' '.$this->reviewer?->last_name,
            ],
            'reviewable_type' => $this->reviewable_type === \App\Models\Product::class ? 'product' : 'shop',
            'reviewable_id' => $this->reviewable_id,
            'rating' => $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'response' => $this->whenLoaded('response', fn () => $this->response ? new ReviewResponseResource($this->response) : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
