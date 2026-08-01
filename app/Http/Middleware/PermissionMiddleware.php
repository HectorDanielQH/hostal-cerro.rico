<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Ensure the authenticated user has the given permission.
     *
     * Usage: ->middleware('permission:reservas.ver|habitaciones.ver')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        $allowed = false;

        if ($user !== null) {
            foreach (explode('|', $permission) as $ability) {
                $ability = trim($ability);

                if ($ability !== '' && $user->hasPermission($ability)) {
                    $allowed = true;
                    break;
                }
            }
        }

        abort_unless($allowed, 403);

        return $next($request);
    }
}
