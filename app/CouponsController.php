<?php

namespace App;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CouponsController extends Controller
{
    public function index(Request $request)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');

        $coupons = Coupon::query()
            ->where('store_id', $activeStorePk)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get([
                'id',
                'code',
                'discount_type',
                'amount',
                'date_expires_gmt',
                'usage_count',
                'usage_limit',
                'updated_at',
            ]);

        return Inertia::render('coupons/Index', [
            'coupons' => $coupons,
        ]);
    }
}
