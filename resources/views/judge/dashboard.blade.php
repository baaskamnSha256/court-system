@extends('layouts.dashboard')

@section('title', 'Хянах самбар')

@section('content')
@include('partials.dashboards.role-hearings-dashboard', [
    'hearingsToday' => $hearingsToday,
    'today' => $today,
    'hearingsCountByDay' => $hearingsCountByDay ?? [],
    'decisionOptions' => $decisionOptions ?? [],
    'decisionCounts' => $decisionCounts ?? [],
    'decisionFilterBaseUrl' => route('judge.hearings.index', [
        'hearing_date_from' => $monthStart->toDateString(),
        'hearing_date_to' => $monthEnd->toDateString(),
    ]),
    'dashboardUrl' => route('judge.dashboard'),
])
@endsection
