<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Хэрэв нэвтэрсэн бол role-оор чиглүүлэх
        if ($user) {
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }
            if ($user->hasRole('judge')) {
                return redirect()->route('judge.dashboard');
            }
            if ($user->hasRole('secretary')) {
                return redirect()->route('secretary.dashboard');
            }
        }

        return $next($request);
    }
}
