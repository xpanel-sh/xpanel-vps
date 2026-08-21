<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // This panel has separate auth entrypoints for admin and client.
        $adminBasePath = trim(config('xpanel.routes.admin_base_path', 'admin'), '/');

        return $request->is($adminBasePath) || $request->is($adminBasePath.'/*')
            ? route('admin.login')
            : route('client.login');
    }
}
