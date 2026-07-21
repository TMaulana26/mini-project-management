<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class TenantTeamMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            if (isset($user->company_id)) {
                app(PermissionRegistrar::class)->setPermissionsTeamId($user->company_id);
            }
        }

        return $next($request);
    }
}
