<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDockerEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('xpanel.docker.enabled', false), 404);

        return $next($request);
    }
}
