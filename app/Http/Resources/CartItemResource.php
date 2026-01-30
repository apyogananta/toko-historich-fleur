<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'qty' => $this->qty,
            'product' => new ProductSummaryResource($this->whenLoaded('product')),
            // 'subtotal' => $this->qty * ($this->product->sale_price ?? $this->product->original_price)
        ];
    }
}
