<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->category_name,
            'slug'      => $this->slug,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
        ];
    }
}
