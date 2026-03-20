<?php

namespace App\Http\Controllers\CourtClerk;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
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

        $monthBaseQuery = Hearing::query()
            ->where('clerk_id', $userId)
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('hearing_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('start_at', [$monthStart, $monthEnd]);
            });

        $monthTotalHearings = (clone $monthBaseQuery)->count();
        $monthIssuedHearings = (clone $monthBaseQuery)->where('notes_handover_issued', true)->count();
        $monthPendingHearings = max(0, $monthTotalHearings - $monthIssuedHearings);

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

        $hearingsCountByDay = Hearing::where('clerk_id', $userId)
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('hearing_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('start_at', [$monthStart, $monthEnd]);
            })
            ->get()
            ->groupBy(function ($h) {
                $date = $h->hearing_date ?: $h->start_at;
                return (int) Carbon::parse($date)->format('j');
            })
            ->map(fn ($group) => $group->count())
            ->toArray();

        return view('court_clerk.dashboard', compact(
            'hearingsToday',
            'today',
            'hearingsCountByDay',
            'monthTotalHearings',
            'monthIssuedHearings',
            'monthPendingHearings'
        ));
    }
}
