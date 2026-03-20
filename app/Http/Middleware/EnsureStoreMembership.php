<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $activeStorePk = (int) $request->session()->get('active_store_id');

        if ($activeStorePk <= 0) {
            abort(403, 'No active store selected.');
        }

        $hasMembership = $user->memberships()
            ->where('store_id', $activeStorePk)
            ->first();

        if (!$hasMembership) {
            abort(403, 'You do not have access to the active store.');
        }

        // Make it available everywhere via request attribute
        $request->attributes->set('active_store_id', $activeStorePk);
        $request->attributes->set('active_store_role', (string) ($hasMembership->role ?? ''));

        return $next($request);
    }
}
