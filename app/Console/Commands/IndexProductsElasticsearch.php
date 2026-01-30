<?php

namespace App\Console\Commands;

use App\Models\Product;
use Elastic\Elasticsearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IndexProductsElasticsearch extends Command
{
    protected $signature = 'es:index-products';
    protected $description = 'Index all products from MySQL to Elasticsearch';
    private $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        parent::__construct();
        $this->elasticsearch = $elasticsearch;
    }

    public function handle()
    {
        $this->info('Indexing products...');
        $productCount = 0;
        $batchSize = 500;

        Product::with('category')->chunkById($batchSize, function ($products) use (&$productCount) {
            $params = ['body' => []];

            foreach ($products as $product) {
                $productCount++;
                $this->info("Preparing product ID: {$product->id}");

                $params['body'][] = [
                    'index' => [
                        '_index' => 'products',
                        '_id' => $product->id
                    ]
                ];

                $productData = $this->prepareProductData($product);
                $params['body'][] = $productData;

                // Log product data being sent
                // $this->info("Data: " . json_encode($productData));
            }

            try {
                if (!empty($params['body'])) {
                    $this->info("Sending batch of " . count($products) . " products to Elasticsearch...");
                    $responses = $this->elasticsearch->bulk($params);
                    if ($responses['errors']) {
                        Log::error('Elasticsearch bulk indexing errors occurred.');
                        foreach ($responses['items'] as $item) {
                            if (isset($item['index']['error'])) {
                                Log::error("Error indexing document ID {$item['index']['_id']}: " . json_encode($item['index']['error']));
                            }
                        }
                    } else {
                        $this->info("Batch sent successfully.");
                    }
                }
            } catch (\Exception $e) {
                Log::error('Elasticsearch bulk indexing failed: ' . $e->getMessage());
                $this->error('Error during bulk indexing: ' . $e->getMessage());
            }

            $params = ['body' => []];
            $this->info("Processed {$productCount} products so far.");
        });

        $this->info("Finished indexing {$productCount} products.");
        return 0; // Indicate success
    }

    private function prepareProductData(Product $product): array
    {
        return [
            'id' => $product->id,
            'product_name' => $product->product_name,
            'category_id' => $product->category_id,
            'category_name' => $product->category ? $product->category->category_name : null,
            'color' => $product->color,
            'brand' => $product->brand,
            'original_price' => $product->original_price,
            'sale_price' => $product->sale_price,
            'size' => $product->size,
            'stock' => $product->stock,
            'weight' => $product->weight,
            'description' => $product->description,
            'slug' => $product->slug,
            'created_at' => $product->created_at->toIso8601String(),
            'updated_at' => $product->updated_at->toIso8601String(),
        ];
    }
}
