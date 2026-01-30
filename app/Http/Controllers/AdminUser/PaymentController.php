<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUser\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Payment::with([
            'order:id,order_number,site_user_id,total_amount',
            'order.user:id,name,email'
        ])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('transaction_id', 'like', "%{$searchTerm}%")
                    ->orWhere('payment_type', 'like', "%{$searchTerm}%")
                    ->orWhereHas('order', function ($orderQuery) use ($searchTerm) {
                        $orderQuery->where('order_number', 'like', "%{$searchTerm}%")
                            ->orWhereHas(
                                'user',
                                fn($userQuery) =>
                                $userQuery->where('name', 'like', "%{$searchTerm}%")
                                    ->orWhere('email', 'like', "%{$searchTerm}%")
                            );
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $payments = $query->paginate($request->input('per_page', 15))->withQueryString();

        return PaymentResource::collection($payments);
    }

    public function show(Payment $payment): PaymentResource
    {
        $payment->load(['order.user']);

        return new PaymentResource($payment);
    }
}
