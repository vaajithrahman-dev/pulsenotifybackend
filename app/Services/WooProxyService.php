<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;

class WooProxyService
{
    private function baseUrl(Store $store): string
    {
        return rtrim((string) $store->store_site_url, '/');
    }

    private function sign(Store $store, string $rawBody, int $timestamp): string
    {
        $toSign = $timestamp . '.' . $rawBody;
        return hash_hmac('sha256', $toSign, $store->signing_secret);
    }

    public function postJson(Store $store, string $path, array $payload): array
    {
        if (empty($store->store_site_url)) {
            return ['ok' => false, 'error' => 'store_site_url_missing'];
        }

        $url = $this->baseUrl($store) . $path;

        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($rawBody === false) {
            return ['ok' => false, 'error' => 'json_encode_failed'];
        }

        $ts = time();
        $sig = $this->sign($store, $rawBody, $ts);

        try {
            $res = Http::timeout(20)
                ->acceptJson()
                ->withHeaders([
                    'X-Pulse-Store-Id' => $store->store_id,
                    'X-Pulse-Timestamp' => (string) $ts,
                    'X-Pulse-Signature' => $sig,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($rawBody, 'application/json')
                ->post($url);

            if (!$res->successful()) {
                return [
                    'ok' => false,
                    'http_status' => $res->status(),
                    'body' => $res->json() ?? $res->body(),
                ];
            }

            return $res->json() ?? ['ok' => true];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => 'http_exception',
                'message' => $e->getMessage(),
            ];
        }
    }

}
