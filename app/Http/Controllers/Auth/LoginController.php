<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();
            $today = Carbon::today();

            $redirectRoute = 'dashboard';
            $todayHearingsCount = 0;

            if ($user && method_exists($user, 'hasRole')) {
                if ($user->hasRole('admin')) {
                    $redirectRoute = 'admin.dashboard';
                } elseif ($user->hasRole('judge')) {
                    $redirectRoute = 'judge.dashboard';
                    $todayHearingsCount = Hearing::query()
                        ->whereDate('start_at', $today)
                        ->whereHas('judges', fn ($query) => $query->where('users.id', $user->id))
                        ->count();
                } elseif ($user->hasRole('secretary')) {
                    $redirectRoute = 'secretary.dashboard';
                } elseif ($user->hasRole('court_clerk')) {
                    $redirectRoute = 'court_clerk.dashboard';
                } elseif ($user->hasRole('prosecutor')) {
                    $redirectRoute = 'prosecutor.dashboard';
                    $todayHearingsCount = Hearing::query()
                        ->whereDate('start_at', $today)
                        ->where(function ($query) use ($user) {
                            $query->where('prosecutor_id', $user->id)
                                ->orWhereJsonContains('prosecutor_ids', $user->id);
                        })
                        ->count();
                } elseif ($user->hasRole('info_desk')) {
                    $redirectRoute = 'info_desk.dashboard';
                } elseif ($user->hasRole('lawyer')) {
                    $redirectRoute = 'lawyer.dashboard';
                    $todayHearingsCount = Hearing::query()
                        ->whereDate('start_at', $today)
                        ->where(function ($query) use ($user) {
                            $query->whereJsonContains('defendant_lawyers_text', $user->name)
                                ->orWhereJsonContains('victim_lawyers_text', $user->name)
                                ->orWhereJsonContains('victim_legal_rep_lawyers_text', $user->name)
                                ->orWhereJsonContains('civil_plaintiff_lawyers', $user->name)
                                ->orWhereJsonContains('civil_defendant_lawyers', $user->name);
                        })
                        ->count();
                }
            }

            if (in_array($redirectRoute, ['judge.dashboard', 'prosecutor.dashboard', 'lawyer.dashboard'], true)) {
                $request->session()->flash('show_today_hearings_toast', true);
                $request->session()->flash('today_hearings_count', $todayHearingsCount);
            }

            if ($user && method_exists($user, 'hasRole') && $user->hasRole('court_clerk')) {
                $request->session()->flash('show_overdue_toast', true);
            }

            return redirect()->intended(route($redirectRoute));
        }

        return back()->withErrors([
            'email' => 'Нэвтрэх мэдээлэл буруу байна',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
