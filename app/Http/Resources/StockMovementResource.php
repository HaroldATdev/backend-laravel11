<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'quantity'   => $this->quantity,
            'reason'     => $this->reason,
            'product_id' => $this->product_id,
            'product'    => new ProductResource($this->whenLoaded('product')),
            'user_id'    => $this->user_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
