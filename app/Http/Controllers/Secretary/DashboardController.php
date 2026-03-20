<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $dateParam = request('date');
        $today = $dateParam && \Carbon\Carbon::hasFormat($dateParam, 'Y-m-d')
            ? \Carbon\Carbon::parse($dateParam)->startOfDay()
            : \Carbon\Carbon::today();

        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $hearingsToday = Hearing::with(['judges', 'prosecutor'])
            ->where('secretary_id', auth()->id())
            ->whereDate('start_at', $today)
            ->orderBy('start_at')
            ->get();

        $hearingsCountByDay = Hearing::where('secretary_id', auth()->id())
            ->whereBetween('start_at', [$monthStart, $monthEnd])
            ->get()
            ->groupBy(fn ($h) => (int) \Carbon\Carbon::parse($h->start_at)->format('j'))
            ->map(fn ($group) => $group->count())
            ->toArray();

        return view('secretary.dashboard', compact('hearingsToday', 'today', 'hearingsCountByDay'));
    }
}
