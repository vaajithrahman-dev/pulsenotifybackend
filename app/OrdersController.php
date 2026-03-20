<?php

namespace App;

use App\Http\Controllers\Controller;
use App\Models\OrderNote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;


class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');

        $orders = Order::query()
            ->where('store_id', $activeStorePk)
            ->orderByDesc('modified_at_gmt')
            ->orderByDesc('id')
            ->limit(50)
            ->get([
                'id',
                'store_id',
                'order_id',
                'order_number',
                'status',
                'currency',
                'total',
                'billing_first_name',
                'billing_last_name',
                'billing_email',
                'coupon_codes',
                'payment_method_title',
                'modified_at_gmt',
            ]);

        return Inertia::render('orders/Index', [
            'orders' => $orders,
        ]);
    }


    public function show(Request $request, int $orderId)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');

        $order = Order::query()
            ->where('store_id', $activeStorePk)
            ->where('order_id', $orderId)
            ->firstOrFail();

        $snapshot = json_decode($order->order_json, true) ?: [];


        $notes = OrderNote::query()
            ->where('store_id', $activeStorePk)
            ->where('order_id', $orderId)
            ->orderByDesc('id')
            ->get(['id', 'note', 'customer_note', 'actor_user_id', 'created_at']);

        return Inertia::render('orders/Show', [
            'order' => [
                'order_id' => $order->order_id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'currency' => $order->currency,
                'total' => $order->total,
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'shipping_total' => $order->shipping_total,
                'tax_total' => $order->tax_total,
                'payment_method_title' => $order->payment_method_title,
                'coupon_codes' => $order->coupon_codes,
                'created_at_gmt' => $order->created_at_gmt,
                'paid_at_gmt' => $order->paid_at_gmt,
                'modified_at_gmt' => $order->modified_at_gmt,
                'billing_email' => $order->billing_email,
                'billing_first_name' => $order->billing_first_name,
                'billing_last_name' => $order->billing_last_name,
            ],
            'snapshot' => $snapshot,
            'notes' => $notes,
        ]);
    }


    public function showById(Request $request, int $id)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');

        $order = Order::query()
            ->where('store_id', $activeStorePk)
            ->where('id', $id)
            ->firstOrFail();

        $snapshot = json_decode($order->order_json, true) ?: [];

        return Inertia::render('orders/Show', [
            'order' => $order,
            'snapshot' => $snapshot,
        ]);
    }


    public function feed(Request $request)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');

        $since = $request->query('since'); // ISO string from client
        $limit = (int) ($request->query('limit', 25));
        if ($limit < 1)
            $limit = 1;
        if ($limit > 100)
            $limit = 100;

        $q = \App\Models\Order::query()
            ->where('store_id', $activeStorePk);

        if ($since) {
            // compare against modified_at_gmt if available; fallback updated_at
            $sinceTs = strtotime($since);
            if ($sinceTs) {
                $q->where(function ($sub) use ($sinceTs) {
                    $sub->where('modified_at_gmt', '>=', date('Y-m-d H:i:s', $sinceTs))
                        ->orWhere('updated_at', '>=', date('Y-m-d H:i:s', $sinceTs));
                });
            }
        }

        $orders = $q->orderByDesc('modified_at_gmt')
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id',
                'order_id',
                'order_number',
                'status',
                'currency',
                'total',
                'billing_first_name',
                'billing_last_name',
                'billing_email',
                'coupon_codes',
                'payment_method_title',
                'modified_at_gmt',
                'updated_at'
            ]);

        return response()->json([
            'ok' => true,
            'server_time' => now()->toISOString(),
            'orders' => $orders,
        ])->header('Cache-Control', 'no-store');
    }
}
