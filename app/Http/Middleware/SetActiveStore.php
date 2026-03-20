<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveStore
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only for authenticated users (web session)
        if ($request->user()) {
            // If session doesn't have active_store_id, set it to the user's first store
            if (!$request->session()->has('active_store_id')) {
                $firstStoreId = $request->user()
                    ->memberships()
                    ->orderBy('id')
                    ->value('store_id'); // memberships.store_id (FK to stores.id)

                if ($firstStoreId) {
                    $request->session()->put('active_store_id', (int) $firstStoreId);
                }
            }
        }

        return $next($request);
    }
}
