<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
         $primaryImageUrl = null;
         if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
             $primaryImage = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
             if ($primaryImage && $primaryImage->image) {
                 $primaryImageUrl = asset('storage/' . $primaryImage->image);
             }
         }

         return [
             'id' => $this->id,
             'name' => $this->product_name,
             'slug' => $this->slug,
             'stock' => $this->stock,
             'original_price' => $this->original_price,
             'primary_image' => $primaryImageUrl,
         ];
    }
}
