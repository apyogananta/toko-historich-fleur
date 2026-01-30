<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'total_amount' => $this->total_amount,
            'shipping_cost' => $this->shipping_cost,
            'status' => $this->status,
            'order_date' => $this->created_at?->toIso8601String(),
            'last_updated' => $this->updated_at?->toIso8601String(),

            'address' => new AddressResource($this->whenLoaded('address')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'shipment' => new ShipmentResource($this->whenLoaded('shipment')),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}
