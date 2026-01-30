<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUser\ProductReviewResource;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ProductReview::with([
            'user:id,name,email',
            'product:id,product_name'
        ])
            ->latest();

        if ($request->filled('rating')) {
            $rating = filter_var($request->input('rating'), FILTER_VALIDATE_INT);
            if ($rating !== false && $rating >= 1 && $rating <= 5) {
                $query->where('rating', $rating);
            }
        }

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('review', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$searchTerm}%"))
                    ->orWhereHas('product', fn($pq) => $pq->where('product_name', 'like', "%{$searchTerm}%"));
            });
        }

        $reviews = $query->paginate($request->input('per_page', 15))->withQueryString();
        return ProductReviewResource::collection($reviews);
    }

    public function show(ProductReview $review): ProductReviewResource
    {
        $review->loadMissing(['user', 'product']);

        return new ProductReviewResource($review);
    }
}
