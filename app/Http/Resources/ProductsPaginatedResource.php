<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;

class ProductsPaginatedResource extends ResourceCollection
{
    public $collects = CollectionResource::class;

    public function toArray(Request $request): array
    {
        $paginated = parent::toArray($request);

        if (isset($paginated['data']) && is_object($paginated['data'])) {
             Log::debug('ProductsPaginatedResource: Forcing data key to array.');
             $paginated['data'] = array_values((array)$paginated['data']);
        } elseif (isset($paginated['data']) && !is_array($paginated['data'])) {
             Log::warning('ProductsPaginatedResource: Data key is not an object or array.', ['data_type' => gettype($paginated['data'])]);
             $paginated['data'] = [];
        }

        unset($paginated['aggregations']);

        return $paginated;
    }
}
