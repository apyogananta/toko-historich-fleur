<?php

namespace App\Http\Resources\AdminUser;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource === null) {
            return [];
        }

        return [
            'id' => $this->id,
            'recipient_name' => $this->recipient_name,
            'phone_number' => $this->phone_number,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'province' => $this->province,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'is_default' => $this->is_default,
        ];
    }
}
