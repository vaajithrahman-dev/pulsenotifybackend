<?php

namespace App;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventsController extends Controller
{
    public function index(Request $request)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');

        $events = Event::query()
            ->where('store_id', $activeStorePk)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get([
                'id',
                'event_id',
                'event_type',
                'summary',
                'is_feed_only',
                'occurred_at',
                'received_at',
                'payload_json',
            ]);

        // Build a lightweight view model, including order_id if present
        $items = $events->map(function ($e) {
            $payload = json_decode($e->payload_json, true) ?: [];
            // $orderId = $payload['data']['order_id'] ?? $payload['data']['orderId'] ?? $payload['order_id'] ?? null;

            $orderId = null;

            $orderId = $payload['data']['order_id'] ?? $payload['data']['orderId'] ?? $payload['order_id'] ?? null;

            // fallback: check nested "data" for "order" object
            if (!$orderId && isset($payload['data']['order']['order_id'])) {
                $orderId = $payload['data']['order']['order_id'];
            }

            return [
                'id' => $e->id,
                'event_id' => $e->event_id,
                'event_type' => $e->event_type,
                'is_feed_only' => (bool)$e->is_feed_only,
                'occurred_at' => $e->occurred_at,
                'received_at' => $e->received_at,
                'order_id' => $orderId ? (int)$orderId : null,
                'payload' => $payload,
            ];
        });

        return Inertia::render('events/Index', [
            'events' => $items,
        ]);
    }
}
