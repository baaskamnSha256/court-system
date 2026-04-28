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
            'Түдгэлзүүлсэн',
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
        $decidedTotal = (clone $yearQuery)
            ->whereNotNull('notes_decision_status')
            ->whereRaw('TRIM(notes_decision_status) IN ('.implode(',', array_fill(0, count($decidedStatuses), '?')).')', $decidedStatuses)
            ->count();
        $pendingCount = max(0, $totalForYear - $decidedTotal);

        $rawDecisionCounts = (clone $yearQuery)
            ->select(['notes_decision_status'])
            ->whereNotNull('notes_decision_status')
            ->whereRaw('TRIM(notes_decision_status) IN ('.implode(',', array_fill(0, count($decidedStatuses), '?')).')', $decidedStatuses)
            ->get()
            ->groupBy(fn ($row) => trim((string) $row->notes_decision_status))
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
