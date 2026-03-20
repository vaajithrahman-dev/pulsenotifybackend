<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPluginHmac
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $storePublicId = $request->header('X-Pulse-Store-Id');
        $timestamp = $request->header('X-Pulse-Timestamp');
        $signature = $request->header('X-Pulse-Signature');

        if (!$storePublicId || !$timestamp || !$signature) {
            return response()->json(['ok' => false, 'error' => 'missing_hmac_headers'], 401);
        }

        if (!ctype_digit((string) $timestamp)) {
            return response()->json(['ok' => false, 'error' => 'invalid_timestamp'], 401);
        }

        $ts = (int) $timestamp;
        $now = time();

        // 5-minute replay window
        if (abs($now - $ts) > 300) {
            return response()->json(['ok' => false, 'error' => 'timestamp_out_of_window'], 401);
        }

        $store = Store::where('store_id', $storePublicId)->first();

        if (!$store || $store->status !== 'active') {
            return response()->json(['ok' => false, 'error' => 'unknown_or_inactive_store'], 401);
        }

        // IMPORTANT: Use raw request body
        $rawBody = $request->getContent(); // raw bytes as received
        $signedPayload = $timestamp . '.' . $rawBody;

        $expected = hash_hmac('sha256', $signedPayload, $store->signing_secret);

        // Timing-safe compare
        if (!hash_equals($expected, $signature)) {
            return response()->json(['ok' => false, 'error' => 'signature_mismatch'], 401);
        }

        // Attach store model for controllers
        $request->attributes->set('store', $store);

        // Update last_seen_at
        $store->last_seen_at = now();
        $store->save();

        return $next($request);
    }
}
