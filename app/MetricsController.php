<?php

namespace App;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MetricsController extends Controller
{
    public function index(Request $request)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');

        $start = $request->query('start');
        $end = $request->query('end');

        $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $end ? Carbon::parse($end)->endOfDay() : Carbon::now()->endOfDay();

        $base = Order::query()
            ->where('store_id', $activeStorePk)
            ->whereBetween('paid_at_gmt', [$startDate, $endDate]);

        // Revenue and order count (paid)
        $revenue = (float) $base->clone()->sum('total');
        $orderCount = (int) $base->clone()->count();
        $aov = $orderCount > 0 ? round($revenue / $orderCount, 2) : 0.0;

        // Coupon performance (orders that have coupon_codes)
        $coupons = $base->clone()
            ->whereNotNull('coupon_codes')
            ->where('coupon_codes', '<>', '')
            ->selectRaw('coupon_codes as code, COUNT(*) as orders, SUM(discount_total) as discount_total, SUM(total) as gross_total')
            ->groupBy('coupon_codes')
            ->orderByDesc('orders')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'code' => $row->code,
                    'orders' => (int) $row->orders,
                    'discount_total' => (float) $row->discount_total,
                    'gross_total' => (float) $row->gross_total,
                ];
            });

        return Inertia::render('metrics/Index', [
            'filters' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'revenue' => round($revenue, 2),
                'orders' => $orderCount,
                'aov' => $aov,
            ],
            'coupons' => $coupons,
        ]);
    }
}

