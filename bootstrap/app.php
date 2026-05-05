<?php

use App\Http\Middleware\ForceAppUrlFromRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            if (file_exists(base_path('routes/fortify.php'))) {
                require base_path('routes/fortify.php');
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Laragon / reverse proxy: зөв Host, HTTPS зэргийг таньж session/CSRF зөрөхөөс сэргийлнэ.
        $middleware->trustProxies(at: '*');

        $middleware->web(prepend: [
            ForceAppUrlFromRequest::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\LogAuthenticatedActivity::class,
            \App\Http\Middleware\RedirectIfRole::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Page expired. Refresh and try again.'], 419);
            }

            return redirect()
                ->route('login')
                ->with('csrf_error', 'Хуудасны хугацаа дууссан эсвэл session тохирохгүй байна. Нэг л хаягаар нэвтэрнэ үү (жишээ: localhost болон 127.0.0.1 хольж бүү ашигла). APP_URL-ыг Laragon-ийн домэйнтайгаа адилхан тохируулна уу.');
        });
    })->create();
