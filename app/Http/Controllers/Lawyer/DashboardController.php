<?php

namespace App\Http\Controllers\Lawyer;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use App\Support\HearingDashboardStatistics;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $name = auth()->user()->name;
        $dateParam = request('date');
        $today = $dateParam && Carbon::hasFormat($dateParam, 'Y-m-d')
            ? Carbon::parse($dateParam)->startOfDay()
            : Carbon::today();

        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $yearStart = $today->copy()->startOfYear();
        $yearEnd = $today->copy()->endOfDay();

        $lawyerScope = fn ($q) => $q->where(function ($query) use ($name) {
            $query->whereJsonContains('defendant_lawyers_text', $name)
                ->orWhereJsonContains('victim_lawyers_text', $name)
                ->orWhereJsonContains('victim_legal_rep_lawyers_text', $name)
                ->orWhereJsonContains('civil_plaintiff_lawyers', $name)
                ->orWhereJsonContains('civil_defendant_lawyers', $name);
        });

        $monthQuery = Hearing::query()
            ->where($lawyerScope)
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('hearing_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('start_at', [$monthStart, $monthEnd]);
            });

        $yearQuery = Hearing::query()
            ->where($lawyerScope)
            ->where(function ($q) use ($yearStart, $yearEnd) {
                $q->whereBetween('hearing_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
                    ->orWhereBetween('start_at', [$yearStart, $yearEnd]);
            });

        $hearingsToday = Hearing::with(['judges', 'prosecutor'])
            ->where($lawyerScope)
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

        extract(HearingDashboardStatistics::decisionBreakdown($yearQuery), EXTR_SKIP);

        return view('lawyer.dashboard', compact(
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
