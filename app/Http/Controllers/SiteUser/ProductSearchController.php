<?php

namespace App\Http\Controllers\SiteUser;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductsPaginatedResource;
use App\Models\Product;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Elastic\Transport\Exception\TransportException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductSearchController extends Controller
{
    private Client $elasticsearch;

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function search(Request $request): ProductsPaginatedResource|JsonResponse
    {
        Log::info('Product search request received:', $request->query());

        $searchParams = [];
        $paginator = null;
        $source = 'unknown';
        $totalHits = null;

        try {
            $searchParams = $this->buildElasticsearchParams($request);
            Log::debug('Attempting Elasticsearch Query:', $searchParams);

            $response = $this->elasticsearch->search($searchParams)->asArray();
            Log::info('Elasticsearch query successful.');

            $productIds = collect($response['hits']['hits'] ?? [])->pluck('_id')->toArray();
            $totalHits = is_array($response['hits']['total']) ? $response['hits']['total']['value'] : ($response['hits']['total'] ?? 0);

            $products = $this->fetchProductsFromDatabase($productIds);
            $paginator = $this->createPaginator($products, $totalHits, $request);
            $source = 'Elasticsearch';
        } catch (NoNodeAvailableException | TransportException $e) {
            Log::warning('Elasticsearch connection failed. Falling back to DB search.', ['error' => $e->getMessage()]);
            try {
                $paginator = $this->searchProductsDirectlyFromDb($request);
                $source = 'Database Fallback (ES Connect Fail)';
            } catch (Throwable $dbError) {
                return $this->handleSearchError($dbError, 'Pencarian gagal dan fallback ke database juga bermasalah.', 500);
            }
        } catch (ClientResponseException $e) {
            $statusCode = $e->getCode();
            Log::warning("Elasticsearch client error (Status: {$statusCode}). Falling back to DB search.", ['error' => $e->getMessage(), 'query' => $searchParams]);
            try {
                $paginator = $this->searchProductsDirectlyFromDb($request);
                $source = 'Database Fallback (ES Client Error)';
            } catch (Throwable $dbError) {
                return $this->handleSearchError($dbError, 'Pencarian gagal (ES Client Error) dan fallback ke database juga bermasalah.', 500);
            }
        } catch (ServerResponseException $e) {
            Log::error('Elasticsearch server error. Falling back to DB search.', ['error' => $e->getMessage(), 'code' => $e->getCode()]);
            try {
                $paginator = $this->searchProductsDirectlyFromDb($request);
                $source = 'Database Fallback (ES Server Error)';
            } catch (Throwable $dbError) {
                return $this->handleSearchError($dbError, 'Pencarian gagal (ES Server Error) dan fallback ke database juga bermasalah.', 500);
            }
        } catch (Throwable $e) {
            return $this->handleSearchError($e, 'Terjadi kesalahan internal saat persiapan pencarian.', 500);
        }

        if ($paginator instanceof LengthAwarePaginator) {
            Log::info('Returning search results.', ['source' => $source]);
            return new ProductsPaginatedResource($paginator);
        } else {
            return $this->handleSearchError(new \Exception("Failed to get results from ES or DB fallback."), 'Gagal memuat data produk.', 500);
        }
    }


    private function searchProductsDirectlyFromDb(Request $request): LengthAwarePaginator
    {
        Log::debug("Executing DB fallback search logic.");
        $keyword = $request->query('keyword', '');
        $categoryId = $request->query('category_id');
        $color = $request->query('color');
        $sortBy = $request->query('sort_by', 'updated_at');
        $sortOrder = $request->query('sort_order', 'desc');
        $perPage = filter_var($request->query('per_page', 12), FILTER_VALIDATE_INT, ['options' => ['default' => 12, 'min_range' => 1]]);
        $categoryId = $categoryId ? filter_var($categoryId, FILTER_VALIDATE_INT) : null;

        $query = Product::query()->with(['images', 'category']);

        if (!empty($keyword)) {
            $query->where(function (Builder $q) use ($keyword) {
                $searchTerm = '%' . $keyword . '%';
                $q->where('product_name', 'LIKE', $searchTerm)
                    ->orWhere('description', 'LIKE', $searchTerm)
                    ->orWhere('color', 'LIKE', $searchTerm)
                    ->orWhereHas('category', function (Builder $cq) use ($searchTerm) {
                        $cq->where('category_name', 'LIKE', $searchTerm);
                    });
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($color) {
            $query->where('color', 'LIKE', '%' . $color . '%');
        }

        $allowedSortFields = ['updated_at', 'created_at', 'original_price', 'product_name'];
        $sortColumn = 'updated_at';
        $direction = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === '_score') {
            $sortColumn = 'updated_at';
            $direction = 'desc';
            Log::info("DB Fallback: '_score' sort requested. Falling back to sorting by 'updated_at desc'.");
        } elseif (in_array($sortBy, $allowedSortFields)) {
            $sortColumn = $sortBy;
        } else {
            $sortColumn = 'updated_at';
            $direction = 'desc';
            Log::warning("DB Fallback: Invalid or disallowed 'sort_by' value '{$sortBy}'. Falling back to 'updated_at desc'.");
        }

        $query->orderBy($sortColumn, $direction);

        if ($sortColumn !== 'id') {
            $query->orderBy('id', 'desc');
        }

        Log::debug("Executing Paginated DB Query for fallback with sorting: {$sortColumn} {$direction}.");
        return $query->paginate($perPage);
    }

    private function buildElasticsearchParams(Request $request): array
    {
        $keyword = $request->query('keyword', '');
        $categoryId = $request->query('category_id');
        $color = $request->query('color');
        $sortBy = $request->query('sort_by', '_score');
        $sortOrder = $request->query('sort_order', 'desc');
        $perPage = filter_var($request->query('per_page', 12), FILTER_VALIDATE_INT, ['options' => ['default' => 12]]);
        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
        $categoryId = $categoryId ? filter_var($categoryId, FILTER_VALIDATE_INT) : null;

        $esQueryBody = [
            'query' => [
                'bool' => [
                    'must' => [],
                    'filter' => []
                ]
            ],
            'sort' => [],
            'track_total_hits' => true,
        ];

        if (!empty($keyword)) {
            $esQueryBody['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $keyword,
                    'fields' => [
                        'product_name^5',
                        'category_name^3',
                        'color^3',
                        'description^1'
                    ],
                    'fuzziness' => 'AUTO',
                    'operator' => 'or'
                ]
            ];
            if ($sortBy === 'updated_at' && $sortOrder === 'desc' && empty($keyword)) {
                $sortBy = 'updated_at';
            }
        } else {
            $esQueryBody['query']['bool']['must'][] = ['match_all' => new \stdClass()];
            if ($sortBy === '_score') {
                $sortBy = 'updated_at';
                $sortOrder = 'desc';
            }
        }

        if ($categoryId) {
            $esQueryBody['query']['bool']['filter'][] = ['term' => ['category_id' => $categoryId]];
        }
        if ($color) {
            $esQueryBody['query']['bool']['filter'][] = ['term' => ['color.keyword' => $color]];
        }

        $allowedSortFields = ['updated_at', 'created_at', 'original_price', 'product_name.keyword'];
        $sortDirection = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === '_score' && !empty($keyword)) {
            unset($esQueryBody['sort']);
        } elseif (in_array($sortBy, $allowedSortFields)) {
            $esQueryBody['sort'] = [$sortBy => ['order' => $sortDirection]];
            if ($sortBy !== '_id' && $sortBy !== '_score') {
                $esQueryBody['sort'][] = ['_id' => 'desc'];
            }
        } else {
            $esQueryBody['sort'] = ['updated_at' => ['order' => 'desc']];
            $esQueryBody['sort'][] = ['_id' => 'desc'];
        }


        return [
            'index' => 'products',
            'body' => $esQueryBody,
            'from' => ($page - 1) * $perPage,
            'size' => $perPage,
        ];
    }

    private function fetchProductsFromDatabase(array $productIds): EloquentCollection
    {
        if (empty($productIds)) {
            return new EloquentCollection();
        }
        $products = Product::with(['images', 'category'])
            ->whereIn('id', $productIds)
            ->get()
            ->sortBy(function ($product) use ($productIds) {
                return array_search($product->id, $productIds);
            });

        return $products;

        // Alternatif jika MySQL >= 8 atau MariaDB >= 10.3 dan ingin sorting di DB:
        // return Product::with(['images', 'category'])
        //                ->whereIn('id', $productIds)
        //                ->orderByRaw('FIELD(id, ' . implode(',', $productIds) . ')')
        //                ->get();
    }

    private function createPaginator(EloquentCollection $products, int $totalHits, Request $request): LengthAwarePaginator
    {
        $perPage = filter_var($request->query('per_page', 12), FILTER_VALIDATE_INT, ['options' => ['default' => 12]]);
        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);

        return new LengthAwarePaginator(
            $products,
            $totalHits,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function handleSearchError(Throwable $e, string $logMessage, int $statusCode, bool $returnEmptyResource = false, ?Request $request = null, array $context = []): JsonResponse|ProductsPaginatedResource
    {
        Log::error($logMessage . ': ' . $e->getMessage(), array_merge([
            'exception_class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], $context));

        if ($returnEmptyResource && $request) {
            $perPage = filter_var($request->query('per_page', 12), FILTER_VALIDATE_INT, ['options' => ['default' => 12]]);
            $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
            $emptyPaginator = new LengthAwarePaginator(
                [],
                0,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            return new ProductsPaginatedResource($emptyPaginator);
        }

        $userMessage = ($statusCode >= 500 || $statusCode === 0)
            ? 'Terjadi gangguan pada server pencarian. Silakan coba beberapa saat lagi.'
            : 'Gagal memproses permintaan pencarian Anda.';

        return response()->json(['message' => $userMessage], $statusCode ?: 500);
    }
}
