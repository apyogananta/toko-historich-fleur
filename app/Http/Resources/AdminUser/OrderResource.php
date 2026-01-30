<?php

namespace App\Http\Resources\AdminUser;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource === null) {
            return [];
        }

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'site_user_id' => $this->site_user_id,
            'address_id' => $this->address_id,
            'total_amount' => $this->total_amount,
            'shipping_cost' => $this->shipping_cost,
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),

            'payment_status' => $this->whenLoaded('payment', $this->payment?->status),
            'shipment_status' => $this->whenLoaded('shipment', $this->shipment?->status),

            'user' => new SiteUserResource($this->whenLoaded('user')),
            'address' => new AddressResource($this->whenLoaded('address')),
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}
