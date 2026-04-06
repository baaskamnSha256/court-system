<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Login form харуулах
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Login шалгах
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = trim($data['username']);

        // Утас (8 оронтой) эсэх
        $isPhone = preg_match('/^[0-9]{8}$/', $username) === 1;

        $credentials = $isPhone
            ? ['phone' => $username, 'password' => $data['password']]
            : ['email' => $username, 'password' => $data['password']];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'username' => 'Нэвтрэх мэдээлэл буруу байна.',
            ])->withInput();
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // идэвхгүй хэрэглэгч бол гаргана
        if (isset($user->is_active) && ! $user->is_active) {
            Auth::logout();

            return back()->withErrors([
                'username' => 'Таны эрх идэвхгүй байна. Админтай холбогдоно уу.',
            ]);
        }

        // Role-д суурилсан redirect
        if ($user->hasAnyRole(['admin', 'head_of_department'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('judge')) {
            return redirect()->route('judge.dashboard');
        }
        if ($user->hasRole('secretary')) {
            return redirect()->route('secretary.dashboard');
        }
        if ($user->hasRole('prosecutor')) {
            return redirect()->route('prosecutor.dashboard');
        }
        if ($user->hasRole('info_desk')) {
            return redirect()->route('info_desk.dashboard');
        }
        if ($user->hasRole('court_clerk')) {
            return redirect()->route('court_clerk.dashboard');
        }

        // бусад role-ууд байвал энд нэм (prosecutor, info_desk г.м)
        // if ($user->hasRole('prosecutor')) return redirect()->route('prosecutor.dashboard');

        Auth::logout();
        abort(403, 'Role тохируулаагүй байна');
    }

    /**
     * Logout
     */
    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
