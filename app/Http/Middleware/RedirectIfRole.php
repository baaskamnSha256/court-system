<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfRole
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->routeIs('home')) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->hasAnyRole(['admin', 'head_of_department'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('secretary')) {
            return redirect()->route('secretary.dashboard');
        }
        if ($user->hasRole('court_clerk')) {
            return redirect()->route('court_clerk.dashboard');
        }
        if ($user->hasRole('info_desk')) {
            return redirect()->route('info_desk.dashboard');
        }
        if ($user->hasRole('judge')) {
            return redirect()->route('judge.dashboard');
        }
        if ($user->hasRole('prosecutor')) {
            return redirect()->route('prosecutor.dashboard');
        }
        if ($user->hasRole('lawyer')) {
            return redirect()->route('lawyer.dashboard');
        }

        return $next($request);
    }
}
