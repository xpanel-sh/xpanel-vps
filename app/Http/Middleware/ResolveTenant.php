<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = \Illuminate\Support\Facades\Auth::guard('web')->user();

        if (! $user) {
            return $next($request);
        }

        // Prefer tenant from the user record; fall back to the one detected
        // from the request hostname (set by TenantFromHost middleware).
        $tenant = $user->tenant ?? $request->attributes->get('tenant_host');
        if (! $tenant) {
            abort(403, 'No tenant assigned to this account.');
        }

        if ($tenant->status !== 'active') {
            abort(403, 'This account is currently suspended.');
        }

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
