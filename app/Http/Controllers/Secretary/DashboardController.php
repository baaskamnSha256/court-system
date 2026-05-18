<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Support\HearingDashboardStatistics;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $dateParam = request('date');
        $today = $dateParam && Carbon::hasFormat($dateParam, 'Y-m-d')
            ? Carbon::parse($dateParam)->startOfDay()
            : Carbon::today();

        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $yearStart = $today->copy()->startOfYear();
        $yearEnd = $today->copy()->endOfDay();

        $monthBaseQuery = Hearing::query()
            ->fromTodayOnwards($today)
            ->where('secretary_id', auth()->id())
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('hearing_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('start_at', [$monthStart, $monthEnd]);
            });

        $hearingsToday = Hearing::with(['judges', 'prosecutor'])
            ->where('secretary_id', auth()->id())
            ->where(function ($q) use ($today) {
                $q->whereDate('hearing_date', $today->toDateString())
                    ->orWhereDate('start_at', $today->toDateString());
            })
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

        $decisionStats = HearingDashboardStatistics::dashboardDecisionStats(
            Hearing::query()->where('secretary_id', auth()->id()),
            $today
        );

        return view('secretary.dashboard', array_merge(compact(
            'hearingsToday',
            'today',
            'hearingsCountByDay',
            'yearStart',
            'yearEnd',
            'monthStart',
            'monthEnd'
        ), $decisionStats));
    }
}
