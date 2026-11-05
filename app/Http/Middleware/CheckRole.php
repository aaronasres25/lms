<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Usage: ->middleware('role:qa')  => qa, manager and admin pass (hierarchical, like Odoo implied groups). */
class CheckRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        abort_unless($user && $user->active && $user->atLeast($role), 403, 'You do not have permission to do that.');
        return $next($request);
    }
}
