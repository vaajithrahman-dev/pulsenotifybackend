<?php

namespace App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\Store;
use App\Services\OrderUpsertService;
use App\Services\WooProxyService;
use Illuminate\Http\Request;

class OrderActionsController extends Controller
{
    private const ALLOWED_STATUSES = [
        'pending', 'processing', 'completed', 'on-hold', 'cancelled', 'refunded', 'failed',
    ];

    private function normalizeStatus(string $status): string
    {
        $status = strtolower($status);
        $status = preg_replace('/^wc-/', '', $status);
        $status = str_replace('_', '-', $status);
        return $status;
    }

    private function extractSnapshotFromWpResponse(array $wp): ?array
    {
        // WP may return: { ok:true, snapshot:{...} }
        if (!empty($wp['ok']) && isset($wp['snapshot']) && is_array($wp['snapshot'])) {
            return $wp['snapshot'];
        }

        // Or return snapshot directly
        if (isset($wp['order_id']) && isset($wp['status'])) {
            return $wp;
        }

        return null;
    }

    public function updateStatus(Request $request, int $orderId)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');

        $validated = $request->validate([
            'status' => ['required', 'string', 'max:32'],
        ]);

        $status = $this->normalizeStatus($validated['status']);

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return back()->with('error', 'Invalid status.');
        }

        // Ensure order exists locally for this store
        $order = Order::query()
            ->where('store_id', $activeStorePk)
            ->where('order_id', $orderId)
            ->firstOrFail();

        $store = Store::findOrFail($activeStorePk);

        $proxy = app(WooProxyService::class);
        $upserter = app(OrderUpsertService::class);

        $wp = $proxy->postJson($store, "/wp-json/pulsenotify/v1/orders/{$orderId}/status", [
            'status' => $status,
        ]);

        if (empty($wp['ok']) && !isset($wp['order_id'])) {
            // WP failed (401/403/500 etc)
            return back()->with('error', 'WP update failed: ' . json_encode($wp));
        }

        $snapshot = $this->extractSnapshotFromWpResponse($wp);
        if (!$snapshot) {
            return back()->with('error', 'WP response did not include a valid snapshot.');
        }

        // Upsert fresh snapshot into local DB
        $upserter->upsertSnapshot($activeStorePk, $snapshot);

        // Audit log
        AuditLog::create([
            'store_id' => $activeStorePk,
            'actor_user_id' => $request->user()?->id,
            'action' => 'order.status_updated',
            'context_json' => json_encode([
                'order_id' => $orderId,
                'from' => $order->status,
                'to' => $status,
            ], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);

        return back()->with('success', "Order status updated in WooCommerce.");
    }

    public function addNote(Request $request, int $orderId)
    {
        $activeStorePk = (int) $request->attributes->get('active_store_id');

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:1', 'max:2000'],
            'customer_note' => ['nullable', 'boolean'],
        ]);

        // Ensure order exists locally
        Order::query()
            ->where('store_id', $activeStorePk)
            ->where('order_id', $orderId)
            ->firstOrFail();

        $store = Store::findOrFail($activeStorePk);

        $noteText = trim($validated['note']);
        $customerNote = (bool)($validated['customer_note'] ?? false);

        $proxy = app(WooProxyService::class);

        $wp = $proxy->postJson($store, "/wp-json/pulsenotify/v1/orders/{$orderId}/notes", [
            'note' => $noteText,
            'customer_note' => $customerNote,
        ]);

        if (empty($wp['ok'])) {
            return back()->with('error', 'WP add-note failed: ' . json_encode($wp));
        }

        // Store note locally for UI
        $local = OrderNote::create([
            'store_id' => $activeStorePk,
            'order_id' => $orderId,
            'note' => $noteText,
            'customer_note' => $customerNote,
            'actor_user_id' => $request->user()?->id,
        ]);

        // Audit log
        AuditLog::create([
            'store_id' => $activeStorePk,
            'actor_user_id' => $request->user()?->id,
            'action' => 'order.note_added',
            'context_json' => json_encode([
                'order_id' => $orderId,
                'order_note_id' => $local->id,
                'customer_note' => $customerNote,
                'wp_note_response' => $wp, // optional: keep small; remove if you want
            ], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Note added in WooCommerce.');
    }
}
