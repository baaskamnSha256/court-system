<?php

namespace App\Http\Controllers\CourtClerk;

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

        $monthBaseQuery = Hearing::query()
            ->where('clerk_id', $userId)
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('hearing_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('start_at', [$monthStart, $monthEnd]);
            });

        $yearBaseQuery = Hearing::query()
            ->where('clerk_id', $userId)
            ->where(function ($q) use ($yearStart, $yearEnd) {
                $q->whereBetween('hearing_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
                    ->orWhereBetween('start_at', [$yearStart, $yearEnd]);
            });

        $hearingsToday = Hearing::with(['judges', 'prosecutor'])
            ->where('clerk_id', $userId)
            ->where(function ($q) use ($today) {
                $q->whereDate('hearing_date', $today->toDateString())
                    ->orWhereDate('start_at', $today->toDateString());
            })
            ->orderBy('hearing_date')
            ->orderBy('hour')
            ->orderBy('minute')
            ->orderBy('start_at')
            ->get();

        $hearingsCountByDay = (clone $monthBaseQuery)
            ->get()
            ->groupBy(function ($h) {
                $date = $h->hearing_date ?: $h->start_at;

                return (int) Carbon::parse($date)->format('j');
            })
            ->map(fn ($group) => $group->count())
            ->toArray();

        extract(HearingDashboardStatistics::decisionBreakdown($yearBaseQuery), EXTR_SKIP);

        return view('court_clerk.dashboard', compact(
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
