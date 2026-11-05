<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Usage: ->middleware('perm:books,create')  (resource, action) */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $resource, string $action = 'read')
    {
        $user = $request->user();
        abort_unless($user && $user->isStaff() && $user->canDo($resource, $action), 403,
            "You do not have permission to {$action} {$resource}.");
        return $next($request);
    }
}
