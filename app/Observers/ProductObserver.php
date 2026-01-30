<?php

namespace App\Observers;

use App\Models\Product;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    private $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }
    private function prepareProductData(Product $product): array
    {
        $product->loadMissing('category');

        $colors = [];
        if (!empty($product->color)) {
            $colors = array_filter(array_map('trim', explode(',', strtolower($product->color ?? ''))));
            $colors = array_values(array_unique($colors));
        }

        return [
            'id' => $product->id,
            'product_name' => $product->product_name,
            'category_id' => $product->category_id,
            'category_name' => $product->category ? $product->category->category_name : null,
            'color' => $colors,
            'brand' => $product->brand,
            'original_price' => $product->original_price,
            'sale_price' => $product->sale_price,
            'size' => $product->size,
            'stock' => $product->stock,
            'weight' => $product->weight,
            'description' => $product->description,
            'slug' => $product->slug,
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }

    public function created(Product $product)
    {
        try {
            $this->elasticsearch->index([
                'index' => 'products',
                'id' => $product->id,
                'body' => $this->prepareProductData($product),
            ]);
        } catch (\Exception $e) {
            Log::error('Elasticsearch indexing failed for created product ID ' . $product->id . ': ' . $e->getMessage());
        }
    }

    public function updated(Product $product)
    {
        try {
            $this->elasticsearch->update([
                'index' => 'products',
                'id' => $product->id,
                'body' => [
                    'doc' => $this->prepareProductData($product),
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Product ID ' . $product->id . ' not found in Elasticsearch for update. Attempting to index.');
            $this->created($product);
        } catch (\Exception $e) {
            Log::error('Elasticsearch update failed for product ID ' . $product->id . ': ' . $e->getMessage());
        }
    }

    public function deleted(Product $product)
    {
        try {
            $this->elasticsearch->delete([
                'index' => 'products',
                'id' => $product->id,
            ]);
        } catch (\Exception $e) {
            Log::info('Product ID ' . $product->id . ' already deleted or not found in Elasticsearch.');
        } catch (\Exception $e) {
            Log::error('Elasticsearch deletion failed for product ID ' . $product->id . ': ' . $e->getMessage());
        }
    }
}
