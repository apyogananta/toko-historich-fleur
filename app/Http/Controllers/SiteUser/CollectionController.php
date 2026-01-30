<?php

namespace App\Http\Controllers\SiteUser;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\ProductsPaginatedResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function getAllCategories()
    {
        return CategoryResource::collection(Category::select('id', 'category_name', 'slug', 'image')->orderBy('updated_at', 'desc')->get());
    }

    public function getAllProducts(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $currentPage = $request->input('page', 1);

        return new ProductsPaginatedResource(Product::with([
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image', 'is_primary');
                },
                'category' => function ($query) {
                    $query->select('id', 'category_name', 'slug', 'image');
                }
            ])
                ->orderByRaw("CASE WHEN stock = 0 THEN 1 ELSE 0 END, updated_at DESC")
                ->paginate($perPage));
    }

    public function getLatestProducts()
    {
        return CollectionResource::collection(Product::with([
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image', 'is_primary');
                },
                'category' => function ($query) {
                    $query->select('id', 'category_name', 'slug', 'image');
                }
            ])
                ->where('stock', '>', 0)
                ->latest()
                ->take(5)
                ->get());
    }

    public function getProductDetail($slug)
    {
        try {
            $product = Product::with([
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image', 'is_primary');
                },
                'category' => function ($query) {
                    $query->select('id', 'category_name', 'slug', 'image');
                },
                // 'reviews' => function ($query) { ... } // Jika perlu reviews
            ])
                ->where('slug', $slug)
                ->firstOrFail();

            return new CollectionResource($product);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Produk tidak ditemukan.'], 404);
        }
    }
}
