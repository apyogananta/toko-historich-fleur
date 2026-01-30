<?php

namespace App\Http\Controllers\SiteUser;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartItem;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShoppingCartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cart = ShoppingCart::firstOrCreate(['site_user_id' => $user->id]);

        $cartItems = ShoppingCartItem::where('shopping_cart_id', $cart->id)
            ->with([
                'product' => function ($query) {
                    $query->select('id', 'product_name', 'slug', 'stock', 'weight', 'original_price');
                },
                'product.images' => function ($query) {
                    $query->where('is_primary', true)->select('id', 'product_id', 'image', 'is_primary');
                }
            ])
            ->get();

        return CartItemResource::collection($cartItems);
    }

    public function addToCart(AddToCartRequest $request)
    {
        $user = $request->user();
        $productId = $request->product_id;
        $requestedQty = $request->qty;

        $product = Product::select('id', 'stock')->find($productId);

        $cart = ShoppingCart::firstOrCreate(['site_user_id' => $user->id]);

        $cartItem = ShoppingCartItem::firstOrNew(
            [
                'shopping_cart_id' => $cart->id,
                'product_id'       => $productId,
            ]
        );

        $newTotalQty = ($cartItem->exists ? $cartItem->qty : 0) + $requestedQty;

        if ($product->stock < $newTotalQty) {
            return response()->json([
                'message' => 'Stok produk tidak mencukupi untuk jumlah yang diminta.'
            ], 400); // Bad Request
        }

        $cartItem->qty = $newTotalQty;
        $cartItem->save();

        $cartItem->load(['product' => function ($query) {
            $query->select('id', 'product_name', 'slug', 'stock', 'original_price');
        }, 'product.images' => function ($query) {
            $query->where('is_primary', true)->select('id', 'product_id', 'image', 'is_primary');
        }]);

        return (new CartItemResource($cartItem))
            ->additional(['message' => 'Produk berhasil ditambahkan ke keranjang'])
            ->response()
            ->setStatusCode(201);
    }

    public function updateCartItem(UpdateCartItemRequest $request, $id)
    {
        $user = $request->user();
        $requestedQty = $request->qty;

        $cartItem = ShoppingCartItem::with('product:id,stock')
            ->whereHas('shoppingCart', function ($query) use ($user) {
                $query->where('site_user_id', $user->id);
            })
            ->findOrFail($id);

        if ($cartItem->product->stock < $requestedQty) {
            return response()->json([
                'message' => 'Stok produk tidak mencukupi.'
            ], 400);
        }

        $cartItem->update(['qty' => $requestedQty]);

        $cartItem->load(['product' => function ($query) {
            $query->select('id', 'product_name', 'slug', 'stock', 'original_price');
        }, 'product.images' => function ($query) {
            $query->where('is_primary', true)->select('id', 'product_id', 'image', 'is_primary');
        }]);

        return (new CartItemResource($cartItem))
            ->additional(['message' => 'Jumlah produk berhasil diperbarui']);
    }

    public function removeCartItem(Request $request, $id)
    {
        $user = $request->user();

        $cartItem = ShoppingCartItem::whereHas('shoppingCart', function ($query) use ($user) {
            $query->where('site_user_id', $user->id);
        })
            ->find($id);

        if (!$cartItem) {
            return response()->json(['message' => 'Item keranjang tidak ditemukan.'], 404);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Produk berhasil dihapus dari keranjang'], 200);
    }
}
