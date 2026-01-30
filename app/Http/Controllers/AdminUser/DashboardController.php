<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUser\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    protected array $validSaleStatuses = ['pending', 'processed', 'shipped', 'delivered'];

    public function summary(Request $request): JsonResponse
    {
        $dates = $this->validateAndPrepareDateRange($request);

        $totalSales = Order::whereIn('status', $this->validSaleStatuses)
            ->whereBetween('created_at', [$dates['start'], $dates['end_inclusive']])
            ->sum('total_amount');

        $totalOrders = Order::whereIn('status', $this->validSaleStatuses)
            ->whereBetween('created_at', [$dates['start'], $dates['end_inclusive']])
            ->count();

        $totalUsers = SiteUser::count();
        $totalProducts = Product::count();

        return response()->json([
            'data' => [
                'total_sales' => (int) $totalSales,
                'formatted_total_sales' => 'Rp ' . number_format($totalSales, 0, ',', '.'),
                'total_orders' => $totalOrders,
                'total_users' => $totalUsers,
                'total_products' => $totalProducts,
                'period' => [
                    'start_date' => $dates['start_formatted'],
                    'end_date' => $dates['end_formatted'],
                ]
            ]
        ], 200);
    }

    public function ordersData(Request $request): JsonResponse
    {
        $dates = $this->validateAndPrepareDateRange($request);

        $orders = Order::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as value')
            ->whereIn('status', $this->validSaleStatuses) // Filter status valid
            ->whereBetween('created_at', [$dates['start'], $dates['end_inclusive']])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $formattedData = $this->formatChartData($orders, 'value');

        return response()->json(['data' => $formattedData], 200);
    }

    public function salesData(Request $request): JsonResponse
    {
        $dates = $this->validateAndPrepareDateRange($request);

        $sales = Order::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_amount) as value')
            ->whereIn('status', $this->validSaleStatuses)
            ->whereBetween('created_at', [$dates['start'], $dates['end_inclusive']])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $formattedData = $this->formatChartData($sales, 'value');

        return response()->json(['data' => $formattedData], 200);
    }

    public function recentOrders(Request $request): AnonymousResourceCollection
    {
        $dates = $this->validateAndPrepareDateRange($request, false); // Tanggal opsional

        $query = Order::with(['user:id,name', 'payment:order_id,status'])
            ->orderBy('created_at', 'desc')
            ->limit(5);

        if ($dates['start'] && $dates['end_inclusive']) {
            $query->whereBetween('created_at', [$dates['start'], $dates['end_inclusive']]);
        }

        $orders = $query->get();

        return OrderResource::collection($orders);
    }

    protected function validateAndPrepareDateRange(Request $request, bool $defaultToMonth = true): array
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            'date_format' => 'Format tanggal harus YYYY-MM-DD.'
        ]);

        $startDate = null;
        $endDate = null;

        if (!empty($validated['start_date'])) {
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        } elseif ($defaultToMonth) {
            $startDate = Carbon::now()->startOfMonth();
        }

        if (!empty($validated['end_date'])) {
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();
        } elseif ($defaultToMonth) {
            $endDate = Carbon::now()->endOfDay();
        }

        return [
            'start' => $startDate,
            'end_inclusive' => $endDate,
            'start_formatted' => $startDate?->toDateString(),
            'end_formatted' => $endDate?->toDateString(),
        ];
    }

    protected function formatChartData(Collection $items, string $valueKey = 'value'): Collection
    {
        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

        return $items->map(function ($item) use ($monthNames, $valueKey) {
            $monthName = $monthNames[$item->month] ?? $item->month;
            $label = $monthName . ' ' . $item->year;

            return [
                'name' => $label,
                $valueKey => (int)$item->{$valueKey},
            ];
        });
    }
}
