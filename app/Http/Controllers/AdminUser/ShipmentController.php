<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUser\UpdateShipmentRequest;
use App\Http\Resources\AdminUser\ShipmentResource;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Shipment::query();

        $query->whereHas('order.payment', function ($paymentQuery) {
            $paymentQuery->whereIn('status', ['settlement', 'paid', 'success']);
        });

        $query->with([
            'order:id,order_number,site_user_id,address_id',
            'order.user:id,name',
            'order.address:id,recipient_name'
        ]);

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('tracking_number', 'like', "%{$searchTerm}%")
                    ->orWhere('courier', 'like', "%{$searchTerm}%")
                    ->orWhere('service', 'like', "%{$searchTerm}%")
                    ->orWhereHas('order', function ($orderQuery) use ($searchTerm) {
                        $orderQuery->where('order_number', 'like', "%{$searchTerm}%")
                            ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$searchTerm}%"))
                            ->orWhereHas('address', fn($addrQuery) => $addrQuery->where('recipient_name', 'like', "%{$searchTerm}%"));
                    });
            });
        }

        if ($request->filled('status')) {
            $validStatuses = ['pending', 'shipped', 'delivered'];
            $statusInput = $request->input('status');
            if (in_array($statusInput, $validStatuses)) {
                $query->where('status', $statusInput);
            }
        }

        $query->orderBy('created_at', 'desc');

        $shipments = $query->paginate($request->input('per_page', 15))
            ->withQueryString();

        return ShipmentResource::collection($shipments);
    }

    public function show(Shipment $shipment): ShipmentResource
    {
        $shipment->load([
            'order.user',
            'order.address',
            'order.orderItems.product'
        ]);

        return new ShipmentResource($shipment);
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): JsonResponse
    {
        $validatedData = $request->validated();

        try {
            $shipment->update($validatedData);

            $shipment->load(['order.user', 'order.address']);

            return response()->json([
                'message' => 'Pengiriman berhasil diperbarui.',
                'shipment' => new ShipmentResource($shipment->fresh(['order.user', 'order.address'])),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error updating shipment {$shipment->id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Terjadi kesalahan internal saat memperbarui pengiriman.'
            ], 500);
        }
    }
}
