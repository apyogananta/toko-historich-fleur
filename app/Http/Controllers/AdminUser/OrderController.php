<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUser\UpdateOrderStatusRequest;
use App\Http\Resources\AdminUser\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected array $validOrderStatuses = [
        'cancelled',
        'awaiting_payment',
        'pending',
        'processed',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::with([
            'user:id,name,email',
            'payment:order_id,status',
            'shipment:order_id,status'
        ])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$searchTerm}%")->orWhere('email', 'like', "%{$searchTerm}%"));
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), $this->validOrderStatuses)) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->paginate($request->input('per_page', 15))->withQueryString();

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        $order->load([
            'user',
            'address',
            'orderItems.product.primaryImage',
            'payment',
            'shipment'
        ]);

        return new OrderResource($order);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $validatedData = $request->validated();
        $newStatus = $validatedData['status'];

        try {
            $order->update(['status' => $newStatus]);

            $order->load(['user', 'address', 'orderItems.product', 'payment', 'shipment']);

            return response()->json([
                'message' => 'Status pesanan berhasil diperbarui.',
                'order' => new OrderResource($order->fresh(['user', 'address', 'orderItems.product', 'payment', 'shipment'])),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error updating order status for order {$order->id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Terjadi kesalahan internal saat memperbarui status pesanan.'
            ], 500);
        }
    }
}
