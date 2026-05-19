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
    'decisionFilterBaseUrl' => route('court_clerk.notes.index', \App\Support\HearingDashboardStatistics::dashboardDecisionFilterQuery($today)),
    'dashboardUrl' => route('court_clerk.dashboard'),
])
@endsection
