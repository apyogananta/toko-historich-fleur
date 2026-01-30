<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUser\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        $validOrderStatuses = ['pending', 'processed', 'shipped', 'delivered', 'awaiting_payment', 'cancelled'];

        $validated = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'status' => ['nullable', 'string', Rule::in($validOrderStatuses)],
            'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            'date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'status.in' => 'Filter status tidak valid.'
        ]);

        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->toDateString();
        $endDateInclusive = Carbon::parse($endDate)->addDay()->toDateString();

        $query = Order::query()
            ->with([
                'user:id,name,email',
                'payment:order_id,status',
                'shipment:order_id,status'
            ])
            ->whereIn('status', ['pending', 'processed', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$startDate, $endDateInclusive]);


        if (!empty($validated['search'])) {
            $searchTerm = $validated['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$searchTerm}%")->orWhere('email', 'like', "%{$searchTerm}%"))
                    ->orWhereHas('shipment', fn($sq) => $sq->where('tracking_number', 'like', "%{$searchTerm}%"));
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $aggregateQuery = clone $query;
        $reportSummary = $aggregateQuery->selectRaw('COUNT(*) as total_orders, SUM(total_amount) as total_sales')
            ->first();

        $perPage = $validated['per_page'] ?? 15;
        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return OrderResource::collection($orders)->additional([
            'meta' => [
                'report_summary' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_orders' => $reportSummary->total_orders ?? 0,
                    'total_sales' => (int) ($reportSummary->total_sales ?? 0),
                    'formatted_total_sales' => 'Rp ' . number_format($reportSummary->total_sales ?? 0, 0, ',', '.'),
                ],
            ]
        ]);
    }
}
