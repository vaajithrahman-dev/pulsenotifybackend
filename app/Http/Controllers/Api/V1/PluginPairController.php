<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PluginPairController extends Controller
{
    public function pair(Request $request)
    {
        $validated = $request->validate([
            'pairing_token' => ['required', 'string', 'min:3', 'max:255'],
            'store_site_url' => ['required', 'string', 'max:255'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'account_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $siteUrl = rtrim($validated['store_site_url'], '/');

        // Reuse same store if the same website pairs again
        $store = Store::where('store_site_url', $siteUrl)->first();

        if (!$store) {
            $store = new Store();
            $store->store_id = 'store_' . Str::lower(Str::random(12));
            $store->signing_secret = Str::random(48);
            $store->store_site_url = $siteUrl;
        }

        if (!empty($validated['store_name'])) {
            $store->store_name = $validated['store_name'];
        }
        if (!empty($validated['account_email'])) {
            $store->account_email = $validated['account_email'];
        }

        $store->status = $store->status ?: 'active';
        $store->last_seen_at = now();
        $store->save();

        return response()->json([
            'store_id' => $store->store_id,
            'signing_secret' => $store->signing_secret,
            'store_name' => $store->store_name ?? 'My Store',
            'store_site_url' => $store->store_site_url,
            'account_email' => $store->account_email ?? 'owner@example.com',
        ]);
    }

}
