<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Users whose password was reset by an admin must choose a new one before using the site. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->must_change_password && ! $request->routeIs('password.change', 'password.update', 'logout')) {
            return redirect()->route('password.change')->with('error', 'Please choose a new password to continue.');
        }
        return $next($request);
    }
}
