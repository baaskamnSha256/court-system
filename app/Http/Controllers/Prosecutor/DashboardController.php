<?php

namespace App\Http\Controllers\Prosecutor;

use App\Http\Controllers\Controller;
use App\Models\Hearing;
use Carbon\Carbon;
use Illuminate\Support\Arr;

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

        $monthQuery = Hearing::query()
            ->where(function ($q) use ($userId) {
                $q->where('prosecutor_id', $userId)
                    ->orWhereJsonContains('prosecutor_ids', $userId);
            })
            ->whereBetween('start_at', [$monthStart, $monthEnd]);

        $hearingsToday = Hearing::with(['judges', 'prosecutor'])
            ->where(function ($q) use ($userId) {
                $q->where('prosecutor_id', $userId)
                    ->orWhereJsonContains('prosecutor_ids', $userId);
            })
            ->whereDate('start_at', $today)
            ->orderBy('start_at')
            ->get();

        $hearingsCountByDay = (clone $monthQuery)
            ->get()
            ->groupBy(fn ($h) => (int) Carbon::parse($h->start_at)->format('j'))
            ->map(fn ($group) => $group->count())
            ->toArray();

        $decisionOptions = [
            'Хүлээгдэж буй' => 'Хүлээгдэж буй',
            'Шийдвэрлэсэн' => 'Шийдвэрлэсэн',
            'Хойшилсон' => 'Хойшилсон',
            'Завсарласан' => 'Завсарласан',
            'Прокурорт буцаасан' => 'Прокурорт буцаасан',
            'Яллагдагчийг шүүхэд шилжүүлсэн' => 'Яллагдагчийг шүүхэд шилжүүлсэн',
            '60 хүртэлх хоногоор хойшлуулсан' => '60 хүртэлх хоногоор хойшлуулсан',
        ];

        $rawDecisionCounts = (clone $monthQuery)
            ->select(['notes_decision_status'])
            ->get()
            ->groupBy(fn ($hearing) => trim((string) ($hearing->notes_decision_status ?? '')) === '' ? 'Хүлээгдэж буй' : $hearing->notes_decision_status)
            ->map(fn ($group) => $group->count())
            ->toArray();

        $decisionCounts = [];
        foreach (array_keys($decisionOptions) as $key) {
            $decisionCounts[$key] = (int) Arr::get($rawDecisionCounts, $key, 0);
        }

        return view('prosecutor.dashboard', compact('hearingsToday', 'today', 'hearingsCountByDay', 'decisionOptions', 'decisionCounts'));
    }
}
