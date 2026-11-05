<?php

use App\Exceptions\LmsException;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['role' => CheckRole::class, 'perm' => CheckPermission::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Business-rule violations (the Odoo UserError / ValidationError equivalent)
        $exceptions->render(function (LmsException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        });
    })->create();
