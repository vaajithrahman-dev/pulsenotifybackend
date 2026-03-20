<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Store;
use Illuminate\Http\Request;

class PluginEventsController extends Controller
{
    public function ingest(Request $request)
    {
        /** @var Store $store */
        $store = $request->attributes->get('store');

        $data = $request->json()->all();

        $eventId = $data['event_id'] ?? null;
        $eventType = $data['event_type'] ?? null;

        if (!$eventId || !$eventType) {
            return response()->json(['ok' => false, 'error' => 'missing_event_fields'], 422);
        }

        // Dedupe by (store_id, event_id)
        $existing = Event::where('store_id', $store->id)
            ->where('event_id', $eventId)
            ->first();

        if ($existing) {
            return response()->json(['ok' => true, 'received_event_id' => $existing->event_id, 'deduped' => true]);
        }

        $occurredAt = null;
        if (!empty($data['occurred_at'])) {
            $occurredAt = date('Y-m-d H:i:s', strtotime($data['occurred_at']));
        }

        $payloadJson = json_encode($data, JSON_UNESCAPED_SLASHES);

        $event = new Event();
        $event->store_id = $store->id;
        $event->event_id = (string) $eventId;
        $event->event_type = (string) $eventType;
        $event->occurred_at = $occurredAt;
        $event->received_at = now();
        $event->payload_json = $payloadJson;
        $event->summary = null;

        // Optional feed-only marker
        $event->is_feed_only = (bool)($data['feed_only'] ?? false)
            || (($data['delivery_mode'] ?? '') === 'feed_only');

        $event->save();

        return response()->json([
            'ok' => true,
            'received_event_id' => $event->event_id,
        ]);
    }
}
