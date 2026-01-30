<?php

namespace App\Http\Controllers\SiteUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductReview;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ProductReviewController extends Controller
{
    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    public function store(Request $request, Product $product)
    {
        $this->authorize('create', [ProductReview::class, $product]);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating harus berupa angka antara 1 dan 5.',
            'rating.min' => 'Rating minimal adalah 1.',
            'rating.max' => 'Rating maksimal adalah 5.',
            'review.max' => 'Review tidak boleh lebih dari 1000 karakter.'
        ]);

        $review = ProductReview::create([
            'site_user_id' => Auth::id(),
            'product_id' => $product->id,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        $review->load('user:id,name');

        return response()->json([
            'message' => 'Review berhasil ditambahkan.',
            'review' => $review,
        ], 201);
    }

    public function update(Request $request, ProductReview $review)
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating harus berupa angka antara 1 dan 5.',
            'rating.min' => 'Rating minimal adalah 1.',
            'rating.max' => 'Rating maksimal adalah 5.',
            'review.max' => 'Review tidak boleh lebih dari 1000 karakter.'
        ]);

        $review->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        $review->load('user:id,name');

        return response()->json([
            'message' => 'Review berhasil diperbarui.',
            'review' => $review,
        ], 200);
    }

    public function destroy(ProductReview $review)
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->json(['message' => 'Review berhasil dihapus.'], 200);
    }

    public function checkEligibility(Request $request, Product $product)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['eligible' => false, 'reason' => 'User not logged in.']);
        }

        $isEligible = Gate::allows('create', [ProductReview::class, $product]);

        $reason = '';
        if (!$isEligible) {
            $hasPurchasedAndDelivered = OrderItem::where('product_id', $product->id)
                ->whereHas('order', function ($query) use ($user) {
                    $query->where('site_user_id', $user->id)
                        ->whereHas('shipment', function ($shipmentQuery) {
                            $shipmentQuery->where('status', 'delivered');
                        });
                })
                ->exists();

            if (!$hasPurchasedAndDelivered) {
                $reason = 'Silahkan beli dan konfirmasi pesanan telah sampai jika ingin mereview produk.';
            } else {
                $hasAlreadyReviewed = ProductReview::where('site_user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->exists();
                if ($hasAlreadyReviewed) {
                    $reason = 'Product already reviewed.';
                } else {
                    $reason = 'Unknown eligibility issue.';
                }
            }
        }


        return response()->json([
            'eligible' => $isEligible,
            'reason' => $reason
        ]);
    }
}
