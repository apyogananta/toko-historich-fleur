<?php

namespace App\Http\Controllers\SiteUser;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    public function getCartRecommendations(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $cart = $user->shoppingCart->with('items.product.category', 'items.product.images')->first(); // Eager load yg mungkin perlu

        $limit = $request->query('limit', 6);
        $recommendations = collect();

        if (!$cart || $cart->items->isEmpty()) {
            Log::debug("Recommendation: Cart empty for user {$user->id}. Fetching latest products.");
            $recommendations = Product::with(['images', 'category'])
                ->where('stock', '>', 0)
                ->latest('updated_at')
                ->limit($limit)
                ->get();
        } else {
            $productIdsInCart = $cart->items->pluck('product_id')->unique()->toArray();
            $categoryIdsInCart = $cart->items->pluck('product.category_id')->unique()->toArray();

            Log::debug("Recommendation: User {$user->id} cart details.", [
                'product_ids' => $productIdsInCart,
                'category_ids' => $categoryIdsInCart
            ]);

            $recommendations = Product::with(['images', 'category'])
                ->whereIn('category_id', $categoryIdsInCart)
                ->whereNotIn('id', $productIdsInCart)
                ->where('stock', '>', 0)
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            Log::debug("Recommendation: Found {$recommendations->count()} products in same categories.");

            if ($recommendations->count() < $limit) {
                $needed = $limit - $recommendations->count();
                $excludeIds = array_merge($productIdsInCart, $recommendations->pluck('id')->toArray());
                Log::debug("Recommendation: Need {$needed} more products. Excluding IDs:", $excludeIds);

                $additionalRecs = Product::with(['images', 'category'])
                    ->whereNotIn('id', $excludeIds)
                    ->where('stock', '>', 0)
                    ->latest('updated_at')
                    ->limit($needed)
                    ->get();

                Log::debug("Recommendation: Found {$additionalRecs->count()} additional latest products.");
                $recommendations = $recommendations->merge($additionalRecs);
            }
        }

        return CollectionResource::collection($recommendations);
    }
}
