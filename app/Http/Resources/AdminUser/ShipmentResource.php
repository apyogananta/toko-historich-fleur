<?php

namespace App\Http\Resources\AdminUser;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource === null) {
            return [];
        }

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'courier' => $this->courier,
            'service' => $this->service,
            'tracking_number' => $this->tracking_number,
            'shipping_cost' => $this->shipping_cost,
            'formatted_shipping_cost' => 'Rp ' . number_format($this->shipping_cost, 0, ',', '.'),
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),

            'address' => new AddressResource($this->whenLoaded('order', fn() => $this->order?->address)),
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'user_name' => $this->order->user?->name,
                    'recipient_name' => $this->order->address?->recipient_name,
                ];
            }),
        ];
    }
}
