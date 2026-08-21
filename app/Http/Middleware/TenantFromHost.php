<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Detects the tenant from the incoming hostname so that clients can access the
 * panel via their own domain (e.g. theirdomain.com:2083/client/login).
 *
 * Sets request attribute 'tenant_host' when a matching tenant is found.
 * ResolveTenant picks this up as a fallback when the authenticated user has no
 * tenant record (e.g. during the login flow before auth is checked).
 */
class TenantFromHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost(); // strips port automatically

        // Skip if the request is coming through the main XPanel domain.
        $panelHost = parse_url(config('app.url', ''), PHP_URL_HOST);
        if ($panelHost && strcasecmp($host, $panelHost) === 0) {
            return $next($request);
        }

        // Look for a site whose domain matches the request host.
        $site = Site::with('tenant')
            ->whereRaw('LOWER(domain) = ?', [strtolower($host)])
            ->first();

        if ($site && $site->tenant && $site->tenant->status === 'active') {
            $request->attributes->set('tenant_host', $site->tenant);
        }

        return $next($request);
    }
}
