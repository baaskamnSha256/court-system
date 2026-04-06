<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class HearingDashboardStatistics
{
    /**
     * @return list<string>
     */
    public static function decidedStatuses(): array
    {
        return [
            'Шийдвэрлэсэн',
            'Хойшилсон',
            'Завсарласан',
            'Прокурорт буцаасан',
            'Яллагдагчийг шүүхэд шилжүүлсэн',
            '60 хүртэлх хоногоор хойшлуулсан',
        ];
    }

    /**
     * @return array{decisionOptions: array<string, string>, decisionCounts: array<string, int>}
     */
    public static function decisionBreakdown(Builder $yearQuery): array
    {
        $decidedStatuses = self::decidedStatuses();

        $decisionOptions = [
            'Хүлээгдэж буй' => 'Хүлээгдэж буй',
            ...array_fill_keys($decidedStatuses, null),
        ];
        foreach ($decidedStatuses as $s) {
            $decisionOptions[$s] = $s;
        }

        $totalForYear = (clone $yearQuery)->count();
        $decidedTotal = (clone $yearQuery)->whereIn('notes_decision_status', $decidedStatuses)->count();
        $pendingCount = max(0, $totalForYear - $decidedTotal);

        $rawDecisionCounts = (clone $yearQuery)
            ->select(['notes_decision_status'])
            ->whereIn('notes_decision_status', $decidedStatuses)
            ->get()
            ->groupBy('notes_decision_status')
            ->map(fn ($g) => $g->count())
            ->toArray();

        $decisionCounts = [];
        $decisionCounts['Хүлээгдэж буй'] = $pendingCount;
        foreach ($decidedStatuses as $k) {
            $decisionCounts[$k] = (int) Arr::get($rawDecisionCounts, $k, 0);
        }

        return compact('decisionOptions', 'decisionCounts');
    }
}
