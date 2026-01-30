<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryImageUrl = null;
        if ($this->relationLoaded('images') && $this->images !== null && $this->images->isNotEmpty()) {
            $primaryImage = $this->images->firstWhere('is_primary', true);
            if ($primaryImage && $primaryImage->image) {
                $primaryImageUrl = asset('storage/' . $primaryImage->image);
            } else {
                $firstImage = $this->images->first();
                if ($firstImage && $firstImage->image) {
                    $primaryImageUrl = asset('storage/' . $firstImage->image);
                }
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->product_name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'stock' => $this->stock,
            'weight' => $this->weight,
            'original_price' => $this->original_price,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'primary_image' => $primaryImageUrl,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
