<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Usage: ->middleware('portal:student')  or  ->middleware('portal:teacher') */
class PortalRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        abort_unless($user && $user->active && $user->role === $role, 403, 'This area is only for ' . $role . 's.');
        return $next($request);
    }
}
