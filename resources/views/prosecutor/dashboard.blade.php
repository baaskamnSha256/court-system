@extends('layouts.dashboard')

@section('title', 'Хянах самбар')

@section('content')
@include('partials.dashboards.role-hearings-dashboard', [
    'hearingsToday' => $hearingsToday,
    'today' => $today,
    'hearingsCountByDay' => $hearingsCountByDay ?? [],
    'showDecisionStats' => $showDecisionStats ?? false,
    'decisionOptions' => $decisionOptions ?? [],
    'decisionCounts' => $decisionCounts ?? [],
    'totalScheduledHearings' => $totalScheduledHearings ?? 0,
    'decisionFilterBaseUrl' => route('prosecutor.hearings.index', [
        'hearing_date_from' => \App\Support\HearingDashboardStatistics::DECISION_SUMMARY_SCHEDULED_SINCE,
        'hearing_date_to' => $today->copy()->endOfYear()->toDateString(),
    ]),
    'dashboardUrl' => route('prosecutor.dashboard'),
])
@endsection
