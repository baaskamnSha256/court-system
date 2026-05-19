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
    'decisionFilterBaseUrl' => route('prosecutor.hearings.index', \App\Support\HearingDashboardStatistics::dashboardDecisionFilterQuery($today)),
    'dashboardUrl' => route('prosecutor.dashboard'),
])
@endsection
