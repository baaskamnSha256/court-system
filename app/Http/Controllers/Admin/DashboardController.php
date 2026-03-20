<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use Carbon\Carbon;
use Illuminate\Support\Arr;

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

        $monthQuery = Hearing::query()
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('hearing_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhereBetween('start_at', [$monthStart, $monthEnd]);
            });

        $hearingsToday = Hearing::with(['judges', 'prosecutor'])
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

        $decisionOptions = [
            'Шийдвэрлэсэн' => 'Шийдвэрлэсэн',
            'Хойшилсон' => 'Хойшилсон',
            'Завсарласан' => 'Завсарласан',
            'Прокурорт буцаасан' => 'Прокурорт буцаасан',
            'Яллагдагчийг шүүхэд шилжүүлсэн' => 'Яллагдагчийг шүүхэд шилжүүлсэн',
            '60 хүртэлх хоногоор хойшлуулсан' => '60 хүртэлх хоногоор хойшлуулсан',
        ];

        $rawDecisionCounts = (clone $monthQuery)
            ->select(['notes_decision_status'])
            ->whereNotNull('notes_decision_status')
            ->get()
            ->groupBy('notes_decision_status')
            ->map(fn ($g) => $g->count())
            ->toArray();

        $decisionCounts = [];
        foreach (array_keys($decisionOptions) as $k) {
            $decisionCounts[$k] = (int) Arr::get($rawDecisionCounts, $k, 0);
        }

        return view('admin.dashboard', compact(
            'hearingsToday',
            'today',
            'hearingsCountByDay',
            'decisionOptions',
            'decisionCounts'
        ));
    }
}
