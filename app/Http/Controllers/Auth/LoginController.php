<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->merge([
            'email' => trim((string) $request->input('email', '')),
        ]);

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $v = trim((string) $value);
                    if ($v === '') {
                        return;
                    }
                    if (preg_match('/^[0-9]{8}$/', $v) === 1) {
                        return;
                    }
                    if (! filter_var($v, FILTER_VALIDATE_EMAIL)) {
                        $fail('Зөв имэйл эсвэл 8 оронтой утасны дугаар оруулна уу.');
                    }
                },
            ],
            'password' => ['required'],
        ]);

        $identifier = $validated['email'];
        $isPhone = preg_match('/^[0-9]{8}$/', $identifier) === 1;

        $user = User::query()
            ->when($isPhone, fn ($q) => $q->where('phone', $identifier))
            ->when(! $isPhone, function ($q) use ($identifier) {
                $normalized = mb_strtolower($identifier, 'UTF-8');
                $q->whereRaw('LOWER(email) = ?', [$normalized]);
            })
            ->first();

        $storedHash = $user !== null ? $user->getRawOriginal('password') : null;
        if ($user === null
            || ! is_string($storedHash)
            || $storedHash === ''
            || ! Hash::check($validated['password'], $storedHash)) {
            return back()->withErrors([
                'email' => 'Нэвтрэх мэдээлэл буруу байна.',
            ])->withInput($request->only('email', 'remember'));
        }

        Auth::login($user, $request->boolean('remember'));

        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null && isset($user->is_active) && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Таны эрх идэвхгүй байна. Системийн админтай холбогдоно уу.',
            ])->withInput($request->only('email', 'remember'));
        }

        $user?->loadMissing('roles');

        $resolved = $this->resolveLoginDestination($user, Carbon::today());

        if ($resolved === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Таны дансад системийн эрх тохируулаагүй байна. Системийн админтай холбогдоно уу.',
            ])->withInput($request->only('email', 'remember'));
        }

        [$redirectRoute, $todayHearingsCount] = $resolved;

        if (in_array($redirectRoute, ['judge.dashboard', 'prosecutor.dashboard', 'lawyer.dashboard'], true)) {
            $request->session()->flash('show_today_hearings_toast', true);
            $request->session()->flash('today_hearings_count', $todayHearingsCount);
        }

        if ($user !== null && $this->userHasRoleName($user, 'court_clerk')) {
            $request->session()->flash('show_overdue_toast', true);
        }

        if ($user === null) {
            return redirect('/login');
        }

        $roleNames = $user->roles->pluck('name')->map(fn ($n) => (string) $n)->values()->all();
        $roleLabel = $roleNames !== [] ? implode(', ', $roleNames) : 'эрхгүй';
        app(ActivityLogService::class)->record(
            'auth.login',
            sprintf('Системд нэвтэрлээ (эрх: %s)', $roleLabel),
            null,
            [
                'roles' => $roleNames,
                'remember' => $request->boolean('remember'),
            ],
            (int) $user->getKey(),
        );

        return redirect()->intended(route($redirectRoute));
    }

    /**
     * @return array{0: string, 1: int}|null [route name, today hearings count]
     */
    private function resolveLoginDestination(?User $user, Carbon $today): ?array
    {
        if ($user === null) {
            return null;
        }

        $priority = ['admin', 'head_of_department', 'judge', 'secretary', 'court_clerk', 'prosecutor', 'info_desk', 'lawyer'];

        foreach ($priority as $role) {
            if (! $this->userHasRoleName($user, $role)) {
                continue;
            }

            return match ($role) {
                'admin' => ['admin.dashboard', 0],
                'head_of_department' => ['admin.dashboard', 0],
                'secretary' => ['secretary.dashboard', 0],
                'court_clerk' => ['court_clerk.dashboard', 0],
                'info_desk' => ['info_desk.dashboard', 0],
                'judge' => [
                    'judge.dashboard',
                    Hearing::query()
                        ->whereDate('start_at', $today)
                        ->whereHas('judges', fn ($query) => $query->where('users.id', $user->id))
                        ->count(),
                ],
                'prosecutor' => [
                    'prosecutor.dashboard',
                    Hearing::query()
                        ->whereDate('start_at', $today)
                        ->where(function ($query) use ($user) {
                            $query->where('prosecutor_id', $user->id)
                                ->orWhereJsonContains('prosecutor_ids', $user->id);
                        })
                        ->count(),
                ],
                'lawyer' => [
                    'lawyer.dashboard',
                    Hearing::query()
                        ->whereDate('start_at', $today)
                        ->where(function ($query) use ($user) {
                            $query->whereJsonContains('defendant_lawyers_text', $user->name)
                                ->orWhereJsonContains('victim_lawyers_text', $user->name)
                                ->orWhereJsonContains('victim_legal_rep_lawyers_text', $user->name)
                                ->orWhereJsonContains('civil_plaintiff_lawyers', $user->name)
                                ->orWhereJsonContains('civil_defendant_lawyers', $user->name);
                        })
                        ->count(),
                ],
            };
        }

        return null;
    }

    private function userHasRoleName(User $user, string $role): bool
    {
        $needle = mb_strtolower($role);

        return $user->roles->contains(fn ($r) => mb_strtolower((string) $r->name) === $needle);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $user?->loadMissing('roles');
        $userId = $user?->getKey();
        $roleNames = $user !== null
            ? $user->roles->pluck('name')->map(fn ($n) => (string) $n)->values()->all()
            : [];

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($userId !== null) {
            $roleLabel = $roleNames !== [] ? implode(', ', $roleNames) : 'эрхгүй';
            app(ActivityLogService::class)->record(
                'auth.logout',
                sprintf('Системээс гарлаа (эрх: %s)', $roleLabel),
                null,
                ['roles' => $roleNames],
                (int) $userId,
            );
        }

        return redirect('/login');
    }
}
