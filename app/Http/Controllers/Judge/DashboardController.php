<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Support\HearingDashboardStatistics;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $dateParam = request('date');
        $today = $dateParam && Carbon::hasFormat($dateParam, 'Y-m-d')
            ? Carbon::parse($dateParam)->startOfDay()
            : Carbon::today();

        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $yearStart = $today->copy()->startOfYear();
        $yearEnd = $today->copy()->endOfDay();

        $monthQuery = Hearing::query()
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('hearing_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('start_at', [$monthStart, $monthEnd]);
            })
            ->whereHas('judges', fn ($q) => $q->where('users.id', $userId));

        $yearQuery = Hearing::query()
            ->where(function ($q) use ($yearStart, $yearEnd) {
                $q->whereBetween('hearing_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
                    ->orWhereBetween('start_at', [$yearStart, $yearEnd]);
            })
            ->whereHas('judges', fn ($q) => $q->where('users.id', $userId));

        $hearingsToday = Hearing::with(['judges', 'prosecutor'])
            ->where(function ($q) use ($today) {
                $q->whereDate('hearing_date', $today->toDateString())
                    ->orWhereDate('start_at', $today->toDateString());
            })
            ->whereHas('judges', fn ($q) => $q->where('users.id', $userId))
            ->orderBy('hearing_date')
            ->orderBy('hour')
            ->orderBy('minute')
            ->orderBy('start_at')
            ->get();

        $hearingsCountByDay = (clone $monthQuery)
            ->get()
            ->groupBy(function ($h) {
                $date = $h->hearing_date ?: $h->start_at;

                return (int) Carbon::parse($date)->format('j');
            })
            ->map(fn ($group) => $group->count())
            ->toArray();

        extract(HearingDashboardStatistics::decisionBreakdown($yearQuery), EXTR_SKIP);

        return view('judge.dashboard', compact(
            'hearingsToday',
            'today',
            'hearingsCountByDay',
            'decisionOptions',
            'decisionCounts',
            'monthStart',
            'monthEnd'
        ));
    }
}
