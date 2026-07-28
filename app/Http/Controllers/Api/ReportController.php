<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderMeal;
use App\Models\Booking;
use App\Models\WalletTransaction;
use App\Models\UserCoupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function daily(Request $request)
    {
        $date = $request->date ?: now()->toDateString();

        $orders = Order::whereDate('created_at', $date)->get();
        $orderCount = $orders->count();
        $totalSales = $orders->sum('total');
        $totalTax = $orders->sum('tax');
        $totalDiscount = $orders->sum('discount');

        $bestSelling = OrderMeal::select('meal_id', DB::raw('SUM(quantity) as total_quantity'))
            ->whereHas('order', function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            })
            ->groupBy('meal_id')
            ->orderBy('total_quantity', 'desc')
            ->with('meal')
            ->first();

        $bookings = Booking::whereDate('created_at', $date)->get();
        $bookingCount = $bookings->count();
        $bookingRevenue = $bookings->sum('total_price');

        $deposits = WalletTransaction::where('type', 'deposit')
            ->whereDate('created_at', $date)
            ->sum('amount');

        $refunds = WalletTransaction::where('type', 'refund')
            ->whereDate('created_at', $date)
            ->sum('amount');

        $cancellationFees = WalletTransaction::where('type', 'cancellation_fee')
            ->whereDate('created_at', $date)
            ->sum('amount');

        $couponsUsed = UserCoupon::whereDate('used_at', $date)
            ->with('coupon')
            ->get();

        $couponDiscount = 0;
        foreach ($couponsUsed as $userCoupon) {
            if ($userCoupon->order) {
                $couponDiscount += $userCoupon->order->discount;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'orders' => [
                    'count' => $orderCount,
                    'total_sales' => $totalSales,
                    'total_tax' => $totalTax,
                    'total_discount' => $totalDiscount,
                    'average_order_value' => $orderCount > 0 ? round($totalSales / $orderCount, 2) : 0,
                ],
                'bookings' => [
                    'count' => $bookingCount,
                    'revenue' => $bookingRevenue,
                ],
                'wallet' => [
                    'deposits' => $deposits,
                    'refunds' => $refunds,
                    'cancellation_fees' => $cancellationFees,
                    'net_revenue' => $deposits - $refunds - $cancellationFees,
                ],
                'best_selling_meal' => $bestSelling ? [
                    'name' => $bestSelling->meal->name,
                    'quantity' => $bestSelling->total_quantity,
                    'price' => $bestSelling->meal->price,
                ] : null,
                'coupons' => [
                    'used_count' => $couponsUsed->count(),
                    'total_discount' => $couponDiscount,
                ],
            ],
        ]);
    }

    public function weekly(Request $request)
    {
        $startDate = $request->start_date ?: now()->startOfWeek()->toDateString();
        $endDate = $request->end_date ?: now()->endOfWeek()->toDateString();

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();
        $orderCount = $orders->count();
        $totalSales = $orders->sum('total');

        $dailyOrders = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topMeals = OrderMeal::select('meal_id', DB::raw('SUM(quantity) as total_quantity'))
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->groupBy('meal_id')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->with('meal')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'summary' => [
                    'total_orders' => $orderCount,
                    'total_sales' => $totalSales,
                    'average_daily_orders' => $orderCount / max(1, (new \DateTime($endDate))->diff(new \DateTime($startDate))->days + 1),
                ],
                'daily_breakdown' => $dailyOrders,
                'top_meals' => $topMeals->map(function ($item) {
                    return [
                        'name' => $item->meal->name,
                        'quantity' => $item->total_quantity,
                        'price' => $item->meal->price,
                    ];
                }),
            ],
        ]);
    }

    public function monthly(Request $request)
    {
        $month = $request->month ?: now()->month;
        $year = $request->year ?: now()->year;

        $startDate = now()->setDate($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = now()->setDate($year, $month, 1)->endOfMonth()->toDateString();

        return $this->weekly($request->merge([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));
    }
}
