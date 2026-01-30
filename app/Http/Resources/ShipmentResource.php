<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'courier' => $this->courier,
            'service' => $this->service,
            'tracking_number' => $this->tracking_number,
            'shipping_cost' => $this->shipping_cost,
            'status' => $this->status,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
