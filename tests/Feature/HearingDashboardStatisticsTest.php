<?php

use App\Models\Hearing;
use App\Support\HearingDashboardStatistics;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes pending as total minus decided statuses for the year query', function () {
    $year = now()->year;
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

    $yearStart = now()->copy()->startOfYear();
    $yearEnd = now()->copy()->endOfDay();
    $yearQuery = Hearing::query()
        ->where(function ($q) use ($yearStart, $yearEnd) {
            $q->whereBetween('hearing_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
                ->orWhereBetween('start_at', [$yearStart, $yearEnd]);
        });

    $out = HearingDashboardStatistics::decisionBreakdown($yearQuery);

    expect($out['decisionCounts']['Хүлээгдэж буй'])->toBe(1)
        ->and($out['decisionCounts']['Шийдвэрлэсэн'])->toBe(1);
});
