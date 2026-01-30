<?php

namespace App\Http\Controllers\SiteUser;

use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function getUserOrder(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);

        $orderQuery = Order::where('site_user_id', $user->id);

        $orders = $orderQuery->with([
            'payment:id,order_id,status,payment_type',
            'orderItems' => function ($query) {
                $query->limit(1);
            },
            'orderItems.product' => function ($query) {
                $query->select('id', 'product_name', 'slug');
            },
            'orderItems.product.images' => function ($query) {
                $query->where('is_primary', true)->select('id', 'product_id', 'image', 'is_primary');
            }
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return OrderResource::collection($orders);
    }

    public function showUserOrder(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::with([
            'orderItems.product' => function ($query) {
                $query->select('id', 'product_name', 'slug', 'weight', 'original_price');
            },
            'orderItems.product.images' => function ($query) {
                $query->where('is_primary', true)->select('id', 'product_id', 'image', 'is_primary');
            },
            'address',
            'shipment',
            'payment'
        ])
            ->where('site_user_id', $user->id)
            ->findOrFail($id);

        return new OrderResource($order);
    }

    public function confirmOrderReceived(Order $order): JsonResponse
    {
        if (Auth::id() !== $order->site_user_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $shipment = $order->shipment;

        if (!$shipment) {
            Log::warning("Attempted to confirm received order (ID: {$order->id}) but shipment data not found.");
            return response()->json(['message' => 'Data pengiriman untuk pesanan ini tidak ditemukan.'], 404);
        }

        if ($shipment->status !== 'shipped') {
            if ($shipment->status === 'delivered') {
                return response()->json(['message' => 'Pesanan ini sudah ditandai sebagai diterima.'], 200);
            }
            return response()->json(['message' => 'Pesanan belum dikirim, tidak dapat dikonfirmasi.'], 400);
        }


        try {
            $shipment->update(['status' => 'delivered']);

            return response()->json(['message' => 'Konfirmasi pesanan diterima berhasil.'], 200);
        } catch (\Exception $e) {
            Log::error("Error confirming order received for order {$order->id}, shipment {$shipment->id}: " . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat mengonfirmasi pesanan.'], 500);
        }
    }
}
