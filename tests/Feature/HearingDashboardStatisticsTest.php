<?php

use App\Models\Hearing;
use App\Support\HearingDashboardStatistics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('hides dashboard decision stats before 2026-01-01', function () {
    Carbon::setTestNow('2025-12-31');

    $stats = HearingDashboardStatistics::dashboardDecisionStats(Hearing::query(), Carbon::today());

    expect($stats['showDecisionStats'])->toBeFalse()
        ->and(HearingDashboardStatistics::isDecisionStatsVisible(Carbon::today()))->toBeFalse();

    Carbon::setTestNow();
});

it('shows dashboard decision stats from 2026-01-01 onward', function () {
    Carbon::setTestNow('2026-01-01');

    $stats = HearingDashboardStatistics::dashboardDecisionStats(Hearing::query(), Carbon::today());

    expect($stats['showDecisionStats'])->toBeTrue()
        ->and(HearingDashboardStatistics::isDecisionStatsVisible(Carbon::today()))->toBeTrue();

    Carbon::setTestNow();
});

it('counts scheduled hearings from 2026-01-01 within the decision stats year scope', function () {
    Carbon::setTestNow('2026-06-15');

    Hearing::query()->create([
        'title' => 'Before',
        'case_no' => 'STAT-BEFORE',
        'hearing_date' => '2025-12-15',
        'start_at' => Carbon::parse('2025-12-15')->startOfDay(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'status' => 'scheduled',
    ]);
    Hearing::query()->create([
        'title' => 'Since',
        'case_no' => 'STAT-SINCE',
        'hearing_date' => '2026-01-10',
        'start_at' => Carbon::parse('2026-01-10')->startOfDay(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'status' => 'scheduled',
    ]);

    $scoped = HearingDashboardStatistics::applyDecisionStatsYearScope(Hearing::query(), Carbon::today());

    expect($scoped->count())->toBe(1);

    Carbon::setTestNow();
});

it('computes pending as total minus decided statuses for the year query', function () {
    $todayStr = now()->toDateString();

    Hearing::query()->create([
        'title' => 'A',
        'case_no' => 'STAT-A',
        'hearing_date' => $todayStr,
        'start_at' => now()->copy()->startOfDay(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'status' => 'scheduled',
        'notes_decision_status' => null,
    ]);
    Hearing::query()->create([
        'title' => 'B',
        'case_no' => 'STAT-B',
        'hearing_date' => $todayStr,
        'start_at' => now()->copy()->startOfDay()->addHour(),
        'hour' => 10,
        'minute' => 0,
        'courtroom' => 'A',
        'status' => 'scheduled',
        'notes_decision_status' => 'Шийдвэрлэсэн',
    ]);

    $yearQuery = HearingDashboardStatistics::applyDecisionStatsYearScope(Hearing::query());

    $out = HearingDashboardStatistics::decisionBreakdown($yearQuery);

    expect($out['decisionCounts']['Хүлээгдэж буй'])->toBe(1)
        ->and($out['decisionCounts']['Шийдвэрлэсэн'])->toBe(1)
        ->and($out['decisionCounts']['Түдгэлзүүлсэн'])->toBe(0)
        ->and($out['decisionOptions']['Түдгэлзүүлсэн'])->toBe('Түдгэлзүүлсэн')
        ->and($out['totalScheduledHearings'])->toBe(2);
});
