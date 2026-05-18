<?php

namespace App\Support;

use App\Models\Hearing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class HearingDashboardStatistics
{
    public const DECISION_SUMMARY_SCHEDULED_SINCE = '2026-01-01';

    public static function isDecisionStatsVisible(?Carbon $today = null): bool
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();

        return $today->greaterThanOrEqualTo(self::decisionStatsSinceDate());
    }

    public static function decisionStatsSinceDate(): Carbon
    {
        return Carbon::parse(self::DECISION_SUMMARY_SCHEDULED_SINCE)->startOfDay();
    }

    /**
     * @param  Builder<Hearing>  $query
     * @return Builder<Hearing>
     */
    public static function applyDecisionStatsYearScope(Builder $query, ?Carbon $today = null): Builder
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $since = self::decisionStatsSinceDate();
        $yearEnd = $today->copy()->endOfYear();

        return $query->where(function (Builder $outer) use ($since, $yearEnd) {
            $outer->where(function (Builder $inner) use ($since, $yearEnd) {
                $inner->whereNotNull('hearing_date')
                    ->whereDate('hearing_date', '>=', $since->toDateString())
                    ->whereDate('hearing_date', '<=', $yearEnd->toDateString());
            })->orWhere(function (Builder $inner) use ($since, $yearEnd) {
                $inner->whereBetween('start_at', [$since, $yearEnd->copy()->endOfDay()]);
            });
        });
    }

    /**
     * @param  Builder<Hearing>  $query
     * @return array{
     *     showDecisionStats: bool,
     *     decisionOptions: array<string, string>,
     *     decisionCounts: array<string, int>,
     *     totalScheduledHearings: int
     * }
     */
    public static function dashboardDecisionStats(Builder $query, ?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();

        if (! self::isDecisionStatsVisible($today)) {
            return [
                'showDecisionStats' => false,
                'decisionOptions' => [],
                'decisionCounts' => [],
                'totalScheduledHearings' => 0,
            ];
        }

        $breakdown = self::decisionBreakdown(self::applyDecisionStatsYearScope($query, $today));

        return [
            'showDecisionStats' => true,
            ...$breakdown,
        ];
    }

    public static function countScheduledHearingsSince(?string $sinceDate = null): int
    {
        $since = Carbon::parse($sinceDate ?? self::DECISION_SUMMARY_SCHEDULED_SINCE)->startOfDay();

        return Hearing::query()
            ->where(function ($q) use ($since) {
                $q->whereDate('hearing_date', '>=', $since->toDateString())
                    ->orWhere('start_at', '>=', $since);
            })
            ->count();
    }

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
     * @return array{decisionOptions: array<string, string>, decisionCounts: array<string, int>, totalScheduledHearings: int}
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

        return [
            'decisionOptions' => $decisionOptions,
            'decisionCounts' => $decisionCounts,
            'totalScheduledHearings' => $totalForYear,
        ];
    }
}
