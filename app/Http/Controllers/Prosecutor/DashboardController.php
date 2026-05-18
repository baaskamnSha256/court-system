<?php

namespace App\Http\Controllers\Prosecutor;

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
            ->fromTodayOnwards($today)
            ->where(function ($q) use ($userId) {
                $q->where('prosecutor_id', $userId)
                    ->orWhereJsonContains('prosecutor_ids', $userId);
            })
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('hearing_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('start_at', [$monthStart, $monthEnd]);
            });

        $hearingsToday = Hearing::with(['judges', 'prosecutor'])
            ->where(function ($q) use ($userId) {
                $q->where('prosecutor_id', $userId)
                    ->orWhereJsonContains('prosecutor_ids', $userId);
            })
            ->where(function ($q) use ($today) {
                $q->whereDate('hearing_date', $today->toDateString())
                    ->orWhereDate('start_at', $today->toDateString());
            })
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

        $decisionStats = HearingDashboardStatistics::dashboardDecisionStats(
            Hearing::query()->where(function ($q) use ($userId) {
                $q->where('prosecutor_id', $userId)
                    ->orWhereJsonContains('prosecutor_ids', $userId);
            }),
            $today
        );

        return view('prosecutor.dashboard', array_merge(compact(
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
